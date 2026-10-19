<?php

namespace App\Http\Controllers\Shop;

use App\Exceptions\CheckoutException;
use App\Exceptions\PaymentGatewayException;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\CheckoutService;
use App\Services\OrderPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class OrderController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Shop/Orders/Index', [
            'orders' => $request->user()->orders()
                ->withCount('items')
                ->latest('id')
                ->paginate(10)
                ->through(fn (Order $o) => $this->present($o)),
        ]);
    }

    public function show(Request $request, Order $order, OrderPaymentService $payments): Response
    {
        abort_unless($order->user_id === $request->user()->id, 404);

        // Al volver desde la pasarela sincronizamos con su API (no confiamos en
        // los parámetros de la URL). Cubre el caso en que el webhook aún no llegó.
        $paymentId = $request->query('payment_id') ?? $request->query('collection_id');
        if ($order->isPending() && is_string($paymentId) && $paymentId !== '' && $paymentId !== 'null') {
            try {
                $payments->syncPayment($paymentId, $order);
                $order->refresh();
            } catch (PaymentGatewayException $e) {
                Log::warning('No se pudo sincronizar el pago al retornar', ['order' => $order->number, 'error' => $e->getMessage()]);
            }
        }

        $order->load('items');

        return Inertia::render('Shop/Orders/Show', [
            'order' => [
                ...$this->present($order),
                'shipping_name' => $order->shipping_name,
                'shipping_phone' => $order->shipping_phone,
                'shipping_address' => $order->shipping_address,
                'notes' => $order->notes,
                'reserved_until' => $order->reserved_until?->toIso8601String(),
                'can_pay' => $order->isPending() && $order->reserved_until?->isFuture(),
                'items' => $order->items->map->only(['id', 'name', 'sku', 'unit_price', 'quantity', 'line_total']),
            ],
        ]);
    }

    public function pay(Request $request, Order $order, CheckoutService $checkout): HttpResponse
    {
        abort_unless($order->user_id === $request->user()->id, 404);

        try {
            $session = $checkout->resumePayment($order);
        } catch (CheckoutException $e) {
            return back()->with('error', $e->getMessage());
        }

        return Inertia::location($session->redirectUrl);
    }

    private function present(Order $order): array
    {
        return [
            'number' => $order->number,
            'status' => $order->status->value,
            'status_label' => $order->status->label(),
            'total' => $order->total,
            'items_count' => $order->items_count ?? null,
            'created_at' => $order->created_at->toIso8601String(),
            'paid_at' => $order->paid_at?->toIso8601String(),
        ];
    }
}
