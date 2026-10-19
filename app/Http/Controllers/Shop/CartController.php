<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Models\Product;
use App\Services\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CartController extends Controller
{
    public function __construct(private readonly CartService $cart)
    {
    }

    public function index(Request $request): Response
    {
        return Inertia::render('Shop/Cart', [
            'cart' => $this->cart->summary($request->user()),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:99'],
        ]);

        $product = Product::findOrFail($data['product_id']);
        $this->cart->add($request->user(), $product, $data['quantity']);

        return back()->with('success', "{$product->name} agregado al carrito.");
    }

    public function update(Request $request, CartItem $item): RedirectResponse
    {
        $this->authorizeItem($request, $item);

        $data = $request->validate(['quantity' => ['required', 'integer', 'min:1', 'max:99']]);
        $this->cart->update($item, $data['quantity']);

        return back();
    }

    public function destroy(Request $request, CartItem $item): RedirectResponse
    {
        $this->authorizeItem($request, $item);
        $this->cart->remove($item);

        return back()->with('success', 'Producto quitado del carrito.');
    }

    private function authorizeItem(Request $request, CartItem $item): void
    {
        abort_unless($item->user_id === $request->user()->id, 404);
    }
}
