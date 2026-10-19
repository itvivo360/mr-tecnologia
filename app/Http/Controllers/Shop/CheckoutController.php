<?php

namespace App\Http\Controllers\Shop;

use App\Exceptions\CheckoutException;
use App\Http\Controllers\Controller;
use App\Services\CartService;
use App\Services\CheckoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class CheckoutController extends Controller
{
    public function create(Request $request, CartService $cart): Response|RedirectResponse
    {
        $summary = $cart->summary($request->user());

        if (empty($summary['items'])) {
            return redirect()->route('cart.index')->with('error', 'Tu carrito está vacío.');
        }

        return Inertia::render('Shop/Checkout', [
            'cart' => $summary,
            'defaults' => [
                'shipping_name' => $request->user()->name,
                'shipping_phone' => $request->user()->phone,
            ],
            'reservationMinutes' => config('shop.order_reservation_minutes'),
        ]);
    }

    public function store(Request $request, CheckoutService $checkout): HttpResponse
    {
        $data = $request->validate([
            'shipping_name' => ['required', 'string', 'max:255'],
            'shipping_phone' => ['required', 'string', 'max:30'],
            'shipping_address' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            [, $session] = $checkout->placeOrder($request->user(), $data);
        } catch (CheckoutException $e) {
            return back()->with('error', $e->getMessage());
        }

        // Redirección externa (Mercado Pago) desde una petición Inertia.
        return Inertia::location($session->redirectUrl);
    }
}
