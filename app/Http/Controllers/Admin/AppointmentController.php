<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AppointmentStatus;
use App\Http\Controllers\Controller;
use App\Models\SupportAppointment;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AppointmentController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
            'status' => ['nullable', Rule::enum(AppointmentStatus::class)],
        ]);

        $from = CarbonImmutable::parse($filters['from'] ?? today())->startOfDay();
        $to = CarbonImmutable::parse($filters['to'] ?? $from->addDays(13))->endOfDay();

        $appointments = SupportAppointment::query()
            ->with(['user:id,name,email,phone', 'product:id,name', 'order:id,number', 'assignee:id,name'])
            ->whereBetween('starts_at', [$from, $to])
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->orderBy('starts_at')
            ->get()
            ->map(fn (SupportAppointment $a) => [
                'id' => $a->id,
                'starts_at' => $a->starts_at->toIso8601String(),
                'ends_at' => $a->ends_at->toIso8601String(),
                'topic' => config("shop.support.topics.{$a->topic}", $a->topic),
                'description' => $a->description,
                'status' => $a->status->value,
                'status_label' => $a->status->label(),
                'admin_notes' => $a->admin_notes,
                'customer' => $a->user->only(['name', 'email', 'phone']),
                'product' => $a->product?->name,
                'order' => $a->order?->number,
                'assigned_to' => $a->assigned_to,
                'assignee' => $a->assignee?->name,
            ]);

        return Inertia::render('Admin/Appointments', [
            'appointments' => $appointments,
            'filters' => ['from' => $from->toDateString(), 'to' => $to->toDateString(), 'status' => $filters['status'] ?? null],
            'statuses' => collect(AppointmentStatus::cases())->map(fn ($s) => ['value' => $s->value, 'label' => $s->label()]),
            'staff' => User::where('role', User::ROLE_ADMIN)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, SupportAppointment $appointment): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::enum(AppointmentStatus::class)],
            'admin_notes' => ['nullable', 'string', 'max:2000'],
            'assigned_to' => ['nullable', Rule::exists('users', 'id')->where('role', User::ROLE_ADMIN)],
        ]);

        $appointment->update($data);

        return back()->with('success', 'Turno actualizado.');
    }
}
