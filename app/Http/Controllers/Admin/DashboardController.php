<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AppointmentStatus;
use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\Order;
use App\Models\Product;
use App\Models\SupportAppointment;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        $monthStart = now()->startOfMonth();

        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'products' => Product::count(),
                'low_stock' => Product::active()->lowStock()->count(),
                'sales_month' => (int) Order::where('status', OrderStatus::Paid)->where('paid_at', '>=', $monthStart)->sum('total'),
                'orders_month' => Order::where('status', OrderStatus::Paid)->where('paid_at', '>=', $monthStart)->count(),
                'appointments_upcoming' => SupportAppointment::whereIn('status', AppointmentStatus::occupying())->where('starts_at', '>=', now())->count(),
                'orders_review' => Order::where('needs_review', true)->count(),
                'messages_unread' => ContactMessage::unread()->count(),
            ],
            'lowStock' => Product::active()->lowStock()->orderBy('stock')->limit(10)
                ->get(['id', 'name', 'sku', 'stock', 'min_stock']),
            'nextAppointments' => SupportAppointment::with('user:id,name')
                ->whereIn('status', AppointmentStatus::occupying())
                ->where('starts_at', '>=', now())
                ->orderBy('starts_at')
                ->limit(5)
                ->get()
                ->map(fn ($a) => [
                    'id' => $a->id,
                    'starts_at' => $a->starts_at->toIso8601String(),
                    'customer' => $a->user->name,
                    'topic' => config("shop.support.topics.{$a->topic}", $a->topic),
                    'status_label' => $a->status->label(),
                    'status' => $a->status->value,
                ]),
        ]);
    }
}
