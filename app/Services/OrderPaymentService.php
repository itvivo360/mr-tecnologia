<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\StockMovementType;
use App\Exceptions\InsufficientStockException;
use App\Models\Order;
use App\Services\Payments\PaymentGateway;
use App\Services\Payments\PaymentInfo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Aplica a las órdenes el resultado de los pagos. Todas las operaciones son
 * idempotentes: el webhook, el retorno del cliente y los reintentos de la
 * pasarela pueden llegar varias veces o en cualquier orden.
 */
class OrderPaymentService
{
    public function __construct(
        private readonly StockService $stock,
        private readonly PaymentGateway $gateway,
    ) {
    }

    /**
     * Consulta el pago en la pasarela y actualiza la orden asociada.
     * Devuelve la orden afectada o null si el pago no corresponde a ninguna.
     */
    public function syncPayment(string $paymentId, ?Order $expectedOrder = null): ?Order
    {
        $payment = $this->gateway->fetchPayment($paymentId);

        $order = Order::where('number', $payment->externalReference)->first();

        if (! $order) {
            Log::warning('Pago sin orden asociada', ['payment_id' => $paymentId, 'reference' => $payment->externalReference]);

            return null;
        }

        if ($expectedOrder && ! $expectedOrder->is($order)) {
            Log::warning('Pago no corresponde a la orden indicada', ['payment_id' => $paymentId, 'order' => $expectedOrder->number]);

            return null;
        }

        return $this->apply($order, $payment);
    }

    public function apply(Order $order, PaymentInfo $payment): Order
    {
        return DB::transaction(function () use ($order, $payment) {
            /** @var Order $order */
            $order = Order::lockForUpdate()->findOrFail($order->id);

            if ($order->status === OrderStatus::Paid) {
                return $order; // Ya procesada.
            }

            if ($payment->isApproved()) {
                if ($payment->amount !== $order->total || $payment->currency !== $order->currency) {
                    Log::error('Monto del pago no coincide con la orden', [
                        'order' => $order->number,
                        'expected' => [$order->total, $order->currency],
                        'received' => [$payment->amount, $payment->currency],
                    ]);
                    $order->update(['needs_review' => true, 'payment_status' => $payment->rawStatus, 'payment_id' => $payment->id]);

                    return $order;
                }

                // Pago aprobado después de que la reserva se liberó: se intenta volver a reservar.
                if ($order->status->releasesStock()) {
                    $this->reReserve($order);
                }

                $order->update([
                    'status' => OrderStatus::Paid,
                    'payment_id' => $payment->id,
                    'payment_status' => $payment->rawStatus,
                    'paid_at' => now(),
                ]);

                return $order;
            }

            if ($payment->isFinalFailure() && $order->isPending()) {
                $this->releaseStock($order, OrderStatus::Failed, 'Pago rechazado');
                $order->update(['payment_status' => $payment->rawStatus]);

                return $order;
            }

            // Pendiente / en proceso: solo registramos el estado.
            $order->update(['payment_status' => $payment->rawStatus]);

            return $order;
        });
    }

    public function cancel(Order $order, string $reason = 'Orden cancelada'): void
    {
        DB::transaction(function () use ($order, $reason) {
            $locked = Order::lockForUpdate()->findOrFail($order->id);

            if ($locked->isPending()) {
                $this->releaseStock($locked, OrderStatus::Cancelled, $reason);
            }

            $order->refresh();
        });
    }

    /**
     * Expira órdenes pendientes cuya reserva venció. Devuelve cuántas expiró.
     */
    public function expireStale(): int
    {
        $expired = 0;

        Order::query()
            ->where('status', OrderStatus::Pending)
            ->where('needs_review', false) // pagadas con diferencias: las resuelve una persona
            ->where('reserved_until', '<', now())
            // lazyById pagina por id: cambiar el estado dentro del loop no salta filas.
            ->lazyById(100)
            ->each(function (Order $order) use (&$expired) {
                DB::transaction(function () use ($order, &$expired) {
                    $locked = Order::lockForUpdate()->find($order->id);

                    if ($locked && $locked->isPending() && ! $locked->needs_review && $locked->reserved_until?->isPast()) {
                        $this->releaseStock($locked, OrderStatus::Expired, 'Reserva expirada');
                        $expired++;
                    }
                });
            });

        return $expired;
    }

    private function releaseStock(Order $order, OrderStatus $status, string $reason): void
    {
        foreach ($order->items()->with('product')->get() as $item) {
            if ($item->product) {
                $this->stock->apply($item->product, $item->quantity, StockMovementType::Release, null, "{$reason} ({$order->number})", $order);
            }
        }

        $order->update(['status' => $status]);
    }

    private function reReserve(Order $order): void
    {
        foreach ($order->items()->with('product')->get() as $item) {
            if (! $item->product) {
                $order->needs_review = true;
                continue;
            }

            try {
                $this->stock->apply($item->product, -$item->quantity, StockMovementType::Sale, null, "Pago tardío {$order->number}", $order);
            } catch (InsufficientStockException) {
                $order->needs_review = true;
                Log::warning('Pago tardío sin stock suficiente', ['order' => $order->number, 'sku' => $item->sku]);
            }
        }

        $order->save();
    }
}
