<?php

namespace App\Services;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class CartService
{
    /**
     * @return Collection<int, CartItem>
     */
    public function items(User $user): Collection
    {
        return $user->cartItems()
            ->with('product.category')
            ->orderBy('id')
            ->get()
            // Productos eliminados o desactivados desaparecen del carrito.
            ->filter(fn (CartItem $item) => $item->product && $item->product->is_active)
            ->values();
    }

    public function summary(User $user): array
    {
        $items = $this->items($user);

        $lines = $items->map(fn (CartItem $item) => [
            'id' => $item->id,
            'quantity' => $item->quantity,
            'line_total' => $item->quantity * $item->product->price,
            'exceeds_stock' => $item->quantity > $item->product->stock,
            'product' => $item->product->only(['id', 'name', 'slug', 'sku', 'price', 'stock', 'image_url']),
        ]);

        return [
            'items' => $lines->all(),
            'count' => (int) $items->sum('quantity'),
            'subtotal' => (int) $lines->sum('line_total'),
            'has_stock_issues' => $lines->contains('exceeds_stock', true),
        ];
    }

    public function count(User $user): int
    {
        return (int) $user->cartItems()->sum('quantity');
    }

    public function add(User $user, Product $product, int $quantity): CartItem
    {
        $this->assertPurchasable($product);

        $item = $user->cartItems()->firstOrNew(['product_id' => $product->id]);
        $newQuantity = ($item->exists ? $item->quantity : 0) + $quantity;

        $this->assertStock($product, $newQuantity);

        $item->quantity = $newQuantity;
        $item->save();

        return $item;
    }

    public function update(CartItem $item, int $quantity): CartItem
    {
        $this->assertPurchasable($item->product);
        $this->assertStock($item->product, $quantity);

        $item->update(['quantity' => $quantity]);

        return $item;
    }

    public function remove(CartItem $item): void
    {
        $item->delete();
    }

    public function clear(User $user): void
    {
        $user->cartItems()->delete();
    }

    private function assertPurchasable(?Product $product): void
    {
        if (! $product || ! $product->is_active || $product->trashed()) {
            throw ValidationException::withMessages(['product' => 'El producto no está disponible.']);
        }
    }

    private function assertStock(Product $product, int $quantity): void
    {
        if ($quantity > $product->stock) {
            throw ValidationException::withMessages([
                'quantity' => "Solo quedan {$product->stock} unidades de {$product->name}.",
            ]);
        }
    }
}
