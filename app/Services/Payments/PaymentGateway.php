<?php

namespace App\Services\Payments;

use App\Models\Order;
use Illuminate\Http\Request;

interface PaymentGateway
{
    /** Nombre corto que se guarda en orders.payment_gateway. */
    public function name(): string;

    /** Crea la sesión de pago y devuelve dónde redirigir al cliente. */
    public function createCheckout(Order $order): CheckoutSession;

    /** Consulta el estado real de un pago en la pasarela. */
    public function fetchPayment(string $paymentId): PaymentInfo;

    /** Verifica que la notificación venga realmente de la pasarela. */
    public function verifyWebhook(Request $request): bool;

    /** Extrae el id de pago de la notificación, o null si no es una notificación de pago. */
    public function paymentIdFromWebhook(Request $request): ?string;
}
