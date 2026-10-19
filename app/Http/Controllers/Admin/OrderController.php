<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderPaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::enum(OrderStatus::class)],
            'review' => ['nullable', 'boolean'],
        ]);

        $orders = Order::query()
            ->with('user:id,name,email')
            ->withCount('items')
            ->when($filters['q'] ?? null, fn ($q, $term) => $q->where(fn ($w) => $w
                ->where('number', 'like', "%{$term}%")
                ->orWhereHas('user', fn ($u) => $u->where('email', 'like', "%{$term}%")->orWhere('name', 'like', "%{$term}%"))))
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($filters['review'] ?? false, fn ($q) => $q->where('needs_review', true))
            ->latest('id')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Order $o) => [
                'number' => $o->number,
                'customer' => $o->user->name,
                'email' => $o->user->email,
                'status' => $o->status->value,
                'status_label' => $o->status->label(),
                'total' => $o->total,
                'items_count' => $o->items_count,
                'needs_review' => $o->needs_review,
                'created_at' => $o->created_at->toIso8601String(),
            ]);

        return Inertia::render('Admin/Orders/Index', [
            'orders' => $orders,
            'filters' => (object) $filters,
            'statuses' => collect(OrderStatus::cases())->map(fn ($s) => ['value' => $s->value, 'label' => $s->label()]),
        ]);
    }

    public function show(Order $order): Response
    {
        $order->load(['items', 'user:id,name,email,phone']);

        return Inertia::render('Admin/Orders/Show', [
            'order' => [
                ...$order->only(['number', 'total', 'subtotal', 'payment_gateway', 'payment_reference', 'payment_id', 'payment_status', 'needs_review', 'shipping_name', 'shipping_phone', 'shipping_address', 'notes']),
                'status' => $order->status->value,
                'status_label' => $order->status->label(),
                'created_at' => $order->created_at->toIso8601String(),
                'paid_at' => $order->paid_at?->toIso8601String(),
                'reserved_until' => $order->reserved_until?->toIso8601String(),
                'customer' => $order->user->only(['name', 'email', 'phone']),
                'items' => $order->items->map->only(['id', 'product_id', 'name', 'sku', 'unit_price', 'quantity', 'line_total']),
            ],
        ]);
    }

    public function cancel(Order $order, OrderPaymentService $payments): RedirectResponse
    {
        if (! $order->isPending()) {
            return back()->with('error', 'Solo se pueden cancelar órdenes pendientes de pago.');
        }

        $payments->cancel($order, 'Cancelada desde el backoffice');

        return back()->with('success', 'Orden cancelada y stock liberado.');
    }

    public function resolveReview(Order $order): RedirectResponse
    {
        $order->update(['needs_review' => false]);

        return back()->with('success', 'Orden marcada como revisada.');
    }
}
