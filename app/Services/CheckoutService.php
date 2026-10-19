<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\StockMovementType;
use App\Exceptions\CheckoutException;
use App\Exceptions\InsufficientStockException;
use App\Exceptions\PaymentGatewayException;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\Payments\CheckoutSession;
use App\Services\Payments\PaymentGateway;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CheckoutService
{
    public function __construct(
        private readonly StockService $stock,
        private readonly OrderPaymentService $payments,
        private readonly PaymentGateway $gateway,
    ) {
    }

    /**
     * Convierte el carrito en una orden pendiente, reserva el stock y crea la
     * sesión de pago. Si la pasarela falla, la orden se cancela, el stock se
     * libera y el carrito vuelve a quedar como estaba.
     *
     * @param  array{shipping_name:string, shipping_phone:string, shipping_address:string, notes?:?string}  $shipping
     * @return array{0: Order, 1: CheckoutSession}
     *
     * @throws CheckoutException
     */
    public function placeOrder(User $user, array $shipping): array
    {
        $order = DB::transaction(function () use ($user, $shipping) {
            $cart = $user->cartItems()->lockForUpdate()->orderBy('product_id')->get();

            if ($cart->isEmpty()) {
                throw new CheckoutException('Tu carrito está vacío.');
            }

            // Bloqueo en orden de id para evitar deadlocks entre compras concurrentes.
            $products = Product::query()
                ->whereIn('id', $cart->pluck('product_id'))
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $lines = $cart->map(function (CartItem $item) use ($products) {
                $product = $products->get($item->product_id);

                if (! $product || ! $product->is_active) {
                    throw new CheckoutException('Uno de los productos del carrito ya no está disponible. Revisa tu carrito.');
                }

                if ($item->quantity > $product->stock) {
                    throw new CheckoutException("Stock insuficiente para {$product->name} (quedan {$product->stock}).");
                }

                return [
                    'product' => $product,
                    'quantity' => $item->quantity,
                    'line_total' => $product->price * $item->quantity,
                ];
            });

            $subtotal = (int) $lines->sum('line_total');

            $order = $user->orders()->create([
                'number' => $this->generateNumber(),
                'status' => OrderStatus::Pending,
                'subtotal' => $subtotal,
                'total' => $subtotal,
                'currency' => config('shop.currency'),
                'payment_gateway' => $this->gateway->name(),
                'shipping_name' => $shipping['shipping_name'],
                'shipping_phone' => $shipping['shipping_phone'],
                'shipping_address' => $shipping['shipping_address'],
                'notes' => $shipping['notes'] ?? null,
                'reserved_until' => now()->addMinutes(config('shop.order_reservation_minutes')),
            ]);

            foreach ($lines as $line) {
                /** @var Product $product */
                $product = $line['product'];

                $order->items()->create([
                    'product_id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'unit_price' => $product->price,
                    'quantity' => $line['quantity'],
                    'line_total' => $line['line_total'],
                ]);

                try {
                    $this->stock->apply($product, -$line['quantity'], StockMovementType::Sale, $user, "Orden {$order->number}", $order);
                } catch (InsufficientStockException $e) {
                    throw new CheckoutException($e->getMessage(), 0, $e);
                }
            }

            $user->cartItems()->delete();

            return $order;
        });

        try {
            $session = $this->gateway->createCheckout($order);
        } catch (PaymentGatewayException $e) {
            $this->payments->cancel($order, 'No se pudo iniciar el pago');
            $this->restoreCart($order);

            throw new CheckoutException($e->getMessage(), 0, $e);
        }

        $order->update(['payment_reference' => $session->reference]);

        return [$order, $session];
    }

    /**
     * Permite reintentar el pago de una orden que sigue pendiente y con reserva vigente.
     */
    public function resumePayment(Order $order): CheckoutSession
    {
        if (! $order->isPending() || $order->reserved_until?->isPast()) {
            throw new CheckoutException('Esta orden ya no se puede pagar.');
        }

        try {
            $session = $this->gateway->createCheckout($order);
        } catch (PaymentGatewayException $e) {
            throw new CheckoutException($e->getMessage(), 0, $e);
        }

        $order->update(['payment_reference' => $session->reference]);

        return $session;
    }

    private function restoreCart(Order $order): void
    {
        foreach ($order->items()->get() as $item) {
            if ($item->product_id) {
                CartItem::updateOrCreate(
                    ['user_id' => $order->user_id, 'product_id' => $item->product_id],
                    ['quantity' => $item->quantity],
                );
            }
        }
    }

    private function generateNumber(): string
    {
        do {
            $number = 'ORD-'.now()->format('ymd').'-'.Str::upper(Str::random(6));
        } while (Order::where('number', $number)->exists());

        return $number;
    }
}
