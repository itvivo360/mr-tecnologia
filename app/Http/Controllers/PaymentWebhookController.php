<?php

namespace App\Http\Controllers;

use App\Exceptions\PaymentGatewayException;
use App\Services\OrderPaymentService;
use App\Services\Payments\PaymentGateway;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class PaymentWebhookController extends Controller
{
    public function mercadopago(Request $request, PaymentGateway $gateway, OrderPaymentService $payments): Response
    {
        if ($gateway->name() !== 'mercadopago') {
            return response('', 404);
        }

        if (! $gateway->verifyWebhook($request)) {
            Log::warning('Webhook de Mercado Pago con firma inválida', ['ip' => $request->ip()]);

            return response('', 401);
        }

        $paymentId = $gateway->paymentIdFromWebhook($request);

        // Otros tópicos (merchant_order, etc.) se confirman sin procesar.
        if ($paymentId === null) {
            return response('', 200);
        }

        try {
            $payments->syncPayment($paymentId);
        } catch (PaymentGatewayException $e) {
            // 5xx para que Mercado Pago reintente la notificación.
            Log::error('No se pudo procesar el webhook', ['payment_id' => $paymentId, 'error' => $e->getMessage()]);

            return response('', 503);
        }

        return response('', 200);
    }
}
