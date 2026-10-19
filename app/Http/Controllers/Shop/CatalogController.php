<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CatalogController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:100'],
            'sort' => ['nullable', 'in:newest,price_asc,price_desc,name'],
        ]);

        $products = Product::query()
            ->active()
            ->with('category:id,name,slug')
            ->search($filters['q'] ?? null)
            ->when($filters['category'] ?? null, fn ($q, $slug) => $q->whereHas('category', fn ($c) => $c->where('slug', $slug)))
            ->when($filters['sort'] ?? 'newest', fn ($q, $sort) => match ($sort) {
                'price_asc' => $q->orderBy('price'),
                'price_desc' => $q->orderByDesc('price'),
                'name' => $q->orderBy('name'),
                default => $q->latest('id'),
            })
            ->paginate(12)
            ->withQueryString()
            ->through(fn (Product $p) => [
                ...$p->only(['id', 'name', 'slug', 'price', 'stock', 'image_url']),
                'category' => $p->category?->name,
            ]);

        return Inertia::render('Shop/Catalog', [
            'products' => $products,
            'categories' => Category::orderBy('name')->get(['id', 'name', 'slug']),
            'filters' => (object) $filters,
        ]);
    }

    public function show(Product $product): Response
    {
        abort_unless($product->is_active, 404);

        $product->load('category:id,name,slug');

        return Inertia::render('Shop/ProductShow', [
            'product' => [
                ...$product->only(['id', 'name', 'slug', 'sku', 'description', 'price', 'stock', 'image_url']),
                'category' => $product->category?->only(['name', 'slug']),
            ],
            'related' => Product::active()
                ->where('id', '!=', $product->id)
                ->when($product->category_id, fn ($q) => $q->where('category_id', $product->category_id))
                ->inRandomOrder()
                ->limit(4)
                ->get(['id', 'name', 'slug', 'price', 'stock', 'image_path']),
        ]);
    }
}
