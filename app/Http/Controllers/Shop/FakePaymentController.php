<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Payments\FakeGateway;
use App\Services\Payments\PaymentGateway;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Pantalla de pago simulado (solo con PAYMENT_GATEWAY=fake y fuera de producción).
 */
class FakePaymentController extends Controller
{
    public function __construct(PaymentGateway $gateway)
    {
        abort_unless($gateway instanceof FakeGateway, 404);
    }

    public function show(Request $request, Order $order): Response
    {
        abort_unless($order->user_id === $request->user()->id && $order->isPending(), 404);

        return Inertia::render('Shop/FakePayment', [
            'order' => $order->only(['number', 'total']),
        ]);
    }

    public function complete(Request $request, Order $order): RedirectResponse
    {
        abort_unless($order->user_id === $request->user()->id, 404);

        $approved = $request->boolean('approved');

        // Simula el retorno desde la pasarela con el id de pago en la URL.
        return redirect()->route('orders.show', [
            'order' => $order,
            'payment_id' => FakeGateway::paymentIdFor($order, $approved),
        ]);
    }
}
