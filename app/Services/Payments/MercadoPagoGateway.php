<?php

namespace App\Services\Payments;

use App\Exceptions\PaymentGatewayException;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Integración con Mercado Pago Checkout Pro vía API REST.
 * Docs: https://www.mercadopago.cl/developers/es/docs/checkout-pro
 */
class MercadoPagoGateway implements PaymentGateway
{
    public function __construct(
        private readonly string $accessToken,
        private readonly ?string $webhookSecret,
        private readonly string $baseUrl = 'https://api.mercadopago.com',
    ) {
    }

    public function name(): string
    {
        return 'mercadopago';
    }

    public function createCheckout(Order $order): CheckoutSession
    {
        $order->loadMissing('items', 'user');

        $payload = [
            'external_reference' => $order->number,
            'items' => $order->items->map(fn (OrderItem $item) => [
                'id' => $item->sku,
                'title' => $item->name,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'currency_id' => $order->currency,
            ])->values()->all(),
            'payer' => [
                'name' => $order->user->name,
                'email' => $order->user->email,
            ],
            'back_urls' => [
                'success' => route('orders.show', $order),
                'failure' => route('orders.show', $order),
                'pending' => route('orders.show', $order),
            ],
            'auto_return' => 'approved',
            'notification_url' => route('webhooks.mercadopago'),
            'statement_descriptor' => mb_substr(config('app.name'), 0, 22),
            // La preferencia expira junto con la reserva de stock, así no se
            // aprueban pagos de órdenes cuyo stock ya fue liberado.
            'expires' => $order->reserved_until !== null,
            'expiration_date_to' => $order->reserved_until?->toIso8601String(),
        ];

        $response = $this->send(fn () => $this->client()
            ->withHeaders(['X-Idempotency-Key' => 'pref-'.$order->number])
            ->post('/checkout/preferences', $payload));

        if (empty($response['id']) || empty($response['init_point'])) {
            throw new PaymentGatewayException('Mercado Pago no devolvió una preferencia válida.');
        }

        return new CheckoutSession($response['id'], $response['init_point']);
    }

    public function fetchPayment(string $paymentId): PaymentInfo
    {
        $data = $this->send(fn () => $this->client()->get('/v1/payments/'.rawurlencode($paymentId)));

        return new PaymentInfo(
            id: (string) $data['id'],
            status: $this->normalizeStatus((string) ($data['status'] ?? '')),
            rawStatus: (string) ($data['status'] ?? ''),
            externalReference: $data['external_reference'] ?? null,
            amount: (int) round((float) ($data['transaction_amount'] ?? 0)),
            currency: (string) ($data['currency_id'] ?? ''),
        );
    }

    /**
     * Valida la cabecera x-signature (HMAC-SHA256) que Mercado Pago envía en webhooks.
     * Plantilla firmada: "id:{data.id};request-id:{x-request-id};ts:{ts};"
     */
    public function verifyWebhook(Request $request): bool
    {
        if (blank($this->webhookSecret)) {
            // Sin secreto configurado no se puede validar la firma. Igual es seguro
            // seguir porque el estado siempre se consulta a la API, nunca se toma
            // del cuerpo de la notificación.
            Log::warning('MERCADOPAGO_WEBHOOK_SECRET no configurado: webhook sin validar firma.');

            return true;
        }

        $signature = (string) $request->header('x-signature', '');
        $requestId = (string) $request->header('x-request-id', '');

        $parts = [];
        foreach (explode(',', $signature) as $chunk) {
            [$key, $value] = array_pad(explode('=', trim($chunk), 2), 2, null);
            if ($key !== null && $value !== null) {
                $parts[$key] = $value;
            }
        }

        if (empty($parts['ts']) || empty($parts['v1'])) {
            return false;
        }

        // El id viaja en el query string como "data.id" (PHP lo convierte a data_id).
        $dataId = (string) ($request->query('data_id') ?? $request->query('data.id') ?? $request->input('data.id', ''));
        if (ctype_alnum($dataId)) {
            $dataId = strtolower($dataId);
        }

        $manifest = '';
        if ($dataId !== '') {
            $manifest .= "id:{$dataId};";
        }
        if ($requestId !== '') {
            $manifest .= "request-id:{$requestId};";
        }
        $manifest .= "ts:{$parts['ts']};";

        $expected = hash_hmac('sha256', $manifest, $this->webhookSecret);

        return hash_equals($expected, $parts['v1']);
    }

    public function paymentIdFromWebhook(Request $request): ?string
    {
        $type = $request->input('type') ?? $request->query('type') ?? $request->query('topic');

        if ($type !== 'payment') {
            return null;
        }

        $id = $request->input('data.id') ?? $request->query('data_id') ?? $request->query('id');

        return $id !== null && $id !== '' ? (string) $id : null;
    }

    private function normalizeStatus(string $status): string
    {
        return match ($status) {
            'approved' => PaymentInfo::APPROVED,
            'rejected' => PaymentInfo::REJECTED,
            'cancelled', 'refunded', 'charged_back' => PaymentInfo::CANCELLED,
            default => PaymentInfo::PENDING, // pending, in_process, authorized, in_mediation
        };
    }

    private function client(): PendingRequest
    {
        if (blank($this->accessToken)) {
            throw new PaymentGatewayException('Falta MERCADOPAGO_ACCESS_TOKEN.');
        }

        return Http::baseUrl($this->baseUrl)
            ->withToken($this->accessToken)
            ->acceptJson()
            ->timeout(15)
            ->retry(2, 300, throw: false);
    }

    private function send(callable $call): array
    {
        try {
            $response = $call();
            $response->throw();

            return $response->json() ?? [];
        } catch (RequestException|ConnectionException $e) {
            Log::error('Error en Mercado Pago', [
                'status' => $e instanceof RequestException ? $e->response->status() : null,
                'body' => $e instanceof RequestException ? ($e->response->json() ?? $e->response->body()) : $e->getMessage(),
            ]);

            throw new PaymentGatewayException('No pudimos comunicarnos con Mercado Pago. Intenta nuevamente.', 0, $e);
        }
    }
}
