<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Services\Settings;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(Settings $settings): Response
    {
        $featured = $this->featuredProducts();

        return Inertia::render('Home', [
            'hero' => $featured->first(),
            'featured' => $featured->slice(1)->values(),
            'categories' => Category::query()
                ->withCount(['products' => fn ($q) => $q->active()])
                ->orderBy('name')
                ->get(['id', 'name', 'slug'])
                ->filter(fn ($c) => $c->products_count > 0)
                ->values(),
            'services' => $settings->services(),
            'description' => $settings->site()['description'],
        ]);
    }

    /**
     * Productos marcados como destacados en el backoffice. Si no hay ninguno,
     * se muestran los más recientes con stock para que la portada no quede vacía.
     */
    private function featuredProducts(): Collection
    {
        $base = fn () => Product::query()->active()->with('category:id,name');

        $products = $base()->featured()->latest('updated_at')->limit(9)->get();

        if ($products->isEmpty()) {
            $products = $base()->where('stock', '>', 0)->latest('id')->limit(9)->get();
        }

        return $products->map(fn (Product $p) => [
            ...$p->only(['id', 'name', 'slug', 'price', 'stock', 'image_url', 'description']),
            'category' => $p->category?->name,
        ]);
    }
}
