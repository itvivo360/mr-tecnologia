<?php

namespace App\Http\Controllers\Shop;

use App\Enums\OrderStatus;
use App\Exceptions\AppointmentException;
use App\Http\Controllers\Controller;
use App\Models\SupportAppointment;
use App\Services\AppointmentScheduler;
use App\Services\Settings;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class SupportController extends Controller
{
    public function __construct(private readonly AppointmentScheduler $scheduler)
    {
    }

    public function index(Request $request, Settings $settings): Response
    {
        $user = $request->user();
        $config = $settings->support();

        return Inertia::render('Shop/Support', [
            'appointments' => $user->appointments()
                ->with(['product:id,name', 'order:id,number'])
                ->orderByDesc('starts_at')
                ->get()
                ->map(fn (SupportAppointment $a) => [
                    'id' => $a->id,
                    'starts_at' => $a->starts_at->toIso8601String(),
                    'ends_at' => $a->ends_at->toIso8601String(),
                    'topic' => $config['topics'][$a->topic] ?? $a->topic,
                    'description' => $a->description,
                    'status' => $a->status->value,
                    'status_label' => $a->status->label(),
                    'product' => $a->product?->name,
                    'order' => $a->order?->number,
                    'can_cancel' => in_array($a->status->value, ['pending', 'confirmed'], true)
                        && $a->starts_at->gt(now()->addHours($config['min_notice_hours'])),
                ]),
            'topics' => collect($config['topics'])->map(fn ($label, $value) => compact('value', 'label'))->values(),
            // Para asociar el turno a una compra y producto concretos.
            'orders' => $user->orders()
                ->where('status', OrderStatus::Paid)
                ->with('items:id,order_id,product_id,name')
                ->latest('id')
                ->limit(20)
                ->get()
                ->map(fn ($o) => [
                    'id' => $o->id,
                    'number' => $o->number,
                    'items' => $o->items->filter(fn ($i) => $i->product_id)->map(fn ($i) => ['product_id' => $i->product_id, 'name' => $i->name])->values(),
                ]),
            'schedule' => [
                'working_days' => $config['working_days'],
                'horizon_days' => $config['booking_horizon_days'],
                'min_notice_hours' => $config['min_notice_hours'],
            ],
        ]);
    }

    public function slots(Request $request): JsonResponse
    {
        $data = $request->validate(['date' => ['required', 'date_format:Y-m-d']]);

        $date = CarbonImmutable::createFromFormat('Y-m-d', $data['date'], config('app.timezone'));

        return response()->json(['slots' => $this->scheduler->slotsFor($date)]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'starts_at' => ['required', 'date'],
            'topic' => ['required', Rule::in(array_keys(config('shop.support.topics')))],
            'description' => ['required', 'string', 'min:10', 'max:2000'],
            'order_id' => ['nullable', 'integer', Rule::exists('orders', 'id')->where('user_id', $user->id)],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
        ]);

        try {
            $appointment = $this->scheduler->book($user, $data);
        } catch (AppointmentException $e) {
            // Como error de validación, para que el formulario conserve lo escrito.
            throw ValidationException::withMessages(['starts_at' => $e->getMessage()]);
        }

        return back()->with('success', 'Turno reservado para el '.$appointment->starts_at->translatedFormat('l j \d\e F \a \l\a\s H:i').'.');
    }

    public function cancel(Request $request, SupportAppointment $appointment): RedirectResponse
    {
        abort_unless($appointment->user_id === $request->user()->id, 404);

        try {
            $this->scheduler->cancelByCustomer($appointment);
        } catch (AppointmentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Turno cancelado.');
    }
}
