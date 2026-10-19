<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockMovement;
use App\Services\StockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'category_id' => ['nullable', 'integer'],
            'stock' => ['nullable', 'in:low,out'],
            'status' => ['nullable', 'in:active,inactive'],
            'featured' => ['nullable', 'boolean'],
        ]);

        $products = Product::query()
            ->with('category:id,name')
            ->search($filters['q'] ?? null)
            ->when($filters['category_id'] ?? null, fn ($q, $id) => $q->where('category_id', $id))
            ->when(($filters['stock'] ?? null) === 'low', fn ($q) => $q->lowStock())
            ->when(($filters['stock'] ?? null) === 'out', fn ($q) => $q->where('stock', 0))
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('is_active', $s === 'active'))
            ->when($filters['featured'] ?? false, fn ($q) => $q->featured())
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Product $p) => [
                ...$p->only(['id', 'name', 'sku', 'barcode', 'price', 'stock', 'min_stock', 'is_active', 'is_featured', 'image_url']),
                'category' => $p->category?->name,
                'low_stock' => $p->isLowStock(),
            ]);

        return Inertia::render('Admin/Products/Index', [
            'products' => $products,
            'categories' => Category::orderBy('name')->get(['id', 'name']),
            'filters' => (object) $filters,
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Admin/Products/Form', [
            'product' => null,
            'categories' => Category::orderBy('name')->get(['id', 'name']),
            // Permite llegar desde el escáner con el código ya cargado.
            'prefill' => ['barcode' => $request->query('barcode')],
            'defaultMinStock' => config('shop.default_min_stock'),
        ]);
    }

    public function store(ProductRequest $request, StockService $stock): RedirectResponse
    {
        $data = $request->validated();

        $product = DB::transaction(function () use ($data, $request, $stock) {
            $product = Product::create([
                ...Arr::except($data, ['image', 'remove_image', 'initial_stock']),
                'slug' => $this->uniqueSlug($data['name']),
                'image_path' => $request->file('image')?->store('products', 'public'),
            ]);

            if (! empty($data['initial_stock'])) {
                $stock->receive($product, (int) $data['initial_stock'], $request->user(), 'Stock inicial');
            }

            return $product;
        });

        return redirect()->route('admin.products.show', $product)->with('success', 'Producto creado.');
    }

    public function show(Product $product): Response
    {
        $product->load('category:id,name');

        return Inertia::render('Admin/Products/Show', [
            'product' => [
                ...$product->only(['id', 'name', 'sku', 'barcode', 'description', 'price', 'stock', 'min_stock', 'is_active', 'is_featured', 'image_url']),
                'category' => $product->category?->name,
                'low_stock' => $product->isLowStock(),
            ],
            'movements' => $product->stockMovements()
                ->with('user:id,name')
                ->paginate(15)
                ->through(fn (StockMovement $m) => [
                    'id' => $m->id,
                    'type' => $m->type->value,
                    'type_label' => $m->type->label(),
                    'quantity' => $m->quantity,
                    'stock_after' => $m->stock_after,
                    'reason' => $m->reason,
                    'user' => $m->user?->name,
                    'created_at' => $m->created_at->toIso8601String(),
                ]),
        ]);
    }

    public function edit(Product $product): Response
    {
        return Inertia::render('Admin/Products/Form', [
            'product' => $product->only(['id', 'name', 'sku', 'barcode', 'category_id', 'description', 'price', 'min_stock', 'is_active', 'is_featured', 'image_url', 'stock']),
            'categories' => Category::orderBy('name')->get(['id', 'name']),
            'prefill' => [],
            'defaultMinStock' => config('shop.default_min_stock'),
        ]);
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        $data = $request->validated();
        $attributes = Arr::except($data, ['image', 'remove_image', 'initial_stock']);
        $oldImage = $product->image_path;

        if ($request->hasFile('image')) {
            $attributes['image_path'] = $request->file('image')->store('products', 'public');
        } elseif ($request->boolean('remove_image')) {
            $attributes['image_path'] = null;
        }

        if ($product->name !== $data['name']) {
            $attributes['slug'] = $this->uniqueSlug($data['name'], $product->id);
        }

        $product->update($attributes);

        if ($oldImage && $oldImage !== $product->image_path) {
            Storage::disk('public')->delete($oldImage);
        }

        return redirect()->route('admin.products.show', $product)->with('success', 'Producto actualizado.');
    }

    /** Marca o desmarca el producto como destacado en la portada. */
    public function toggleFeatured(Product $product): RedirectResponse
    {
        $product->update(['is_featured' => ! $product->is_featured]);

        return back()->with('success', $product->is_featured
            ? "{$product->name} ahora aparece en la portada."
            : "{$product->name} ya no aparece en la portada.");
    }

    public function destroy(Product $product): RedirectResponse
    {
        // Soft delete: el historial de órdenes y movimientos se conserva.
        $product->update(['is_active' => false]);
        $product->delete();

        return redirect()->route('admin.products.index')->with('success', "Producto {$product->name} eliminado.");
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'producto';
        $slug = $base;
        $i = 2;

        while (Product::withTrashed()->where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}
