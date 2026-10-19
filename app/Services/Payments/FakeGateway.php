<?php

namespace App\Services\Payments;

use App\Models\Order;
use Illuminate\Http\Request;

/**
 * Pasarela simulada para desarrollo: redirige a una pantalla local donde se
 * puede aprobar o rechazar el pago. Nunca habilitar en producción.
 */
class FakeGateway implements PaymentGateway
{
    public function name(): string
    {
        return 'fake';
    }

    public function createCheckout(Order $order): CheckoutSession
    {
        return new CheckoutSession('fake-'.$order->number, route('payments.fake.show', $order));
    }

    public function fetchPayment(string $paymentId): PaymentInfo
    {
        // Formato: fake-{approved|rejected}-{numero_orden}-{total}
        [, $status, $number, $amount] = array_pad(explode('-', $paymentId, 4), 4, null);
        $number = $number !== null ? str_replace('_', '-', $number) : null;

        return new PaymentInfo(
            id: $paymentId,
            status: $status === 'approved' ? PaymentInfo::APPROVED : PaymentInfo::REJECTED,
            rawStatus: (string) $status,
            externalReference: $number,
            amount: (int) $amount,
            currency: config('shop.currency'),
        );
    }

    public function verifyWebhook(Request $request): bool
    {
        return false;
    }

    public function paymentIdFromWebhook(Request $request): ?string
    {
        return null;
    }

    public static function paymentIdFor(Order $order, bool $approved): string
    {
        return sprintf('fake-%s-%s-%d', $approved ? 'approved' : 'rejected', str_replace('-', '_', $order->number), $order->total);
    }
}
