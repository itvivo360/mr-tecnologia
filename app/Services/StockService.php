<?php

namespace App\Services;

use App\Enums\StockMovementType;
use App\Exceptions\InsufficientStockException;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Único punto de cambio del stock. Cada cambio bloquea la fila del producto
 * y deja un movimiento con el saldo resultante, para que el historial siempre
 * cuadre con el stock actual.
 */
class StockService
{
    /**
     * Aplica un delta (positivo o negativo) al stock del producto.
     *
     * @throws InsufficientStockException si el stock quedaría negativo
     */
    public function apply(
        Product $product,
        int $delta,
        StockMovementType $type,
        ?User $user = null,
        ?string $reason = null,
        ?Model $reference = null,
    ): StockMovement {
        if ($delta === 0) {
            throw new InvalidArgumentException('La cantidad del movimiento no puede ser 0.');
        }

        return DB::transaction(function () use ($product, $delta, $type, $user, $reason, $reference) {
            /** @var Product $locked */
            $locked = Product::withTrashed()->lockForUpdate()->findOrFail($product->getKey());

            $newStock = $locked->stock + $delta;

            if ($newStock < 0) {
                throw new InsufficientStockException($locked, abs($delta));
            }

            $locked->forceFill(['stock' => $newStock])->save();
            $product->setRawAttributes($locked->getAttributes(), true);

            return $locked->stockMovements()->create([
                'user_id' => $user?->getKey(),
                'type' => $type,
                'quantity' => $delta,
                'stock_after' => $newStock,
                'reason' => $reason,
                'reference_type' => $reference?->getMorphClass(),
                'reference_id' => $reference?->getKey(),
            ]);
        });
    }

    public function receive(Product $product, int $quantity, ?User $user = null, ?string $reason = null): StockMovement
    {
        return $this->apply($product, abs($quantity), StockMovementType::In, $user, $reason);
    }

    public function withdraw(Product $product, int $quantity, ?User $user = null, ?string $reason = null): StockMovement
    {
        return $this->apply($product, -abs($quantity), StockMovementType::Out, $user, $reason);
    }

    /**
     * Fija el stock a un valor contado (inventario). Devuelve null si no hubo diferencia.
     */
    public function setCounted(Product $product, int $counted, ?User $user = null, ?string $reason = null): ?StockMovement
    {
        return DB::transaction(function () use ($product, $counted, $user, $reason) {
            $current = Product::withTrashed()->lockForUpdate()->findOrFail($product->getKey())->stock;
            $delta = $counted - $current;

            if ($delta === 0) {
                return null;
            }

            return $this->apply($product, $delta, StockMovementType::Adjustment, $user, $reason ?? 'Ajuste por inventario');
        });
    }
}
