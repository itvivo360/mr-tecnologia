<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Exceptions\AppointmentException;
use App\Models\SupportAppointment;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Agenda de soporte técnico: genera los bloques del día según la
 * configuración (config/shop.php > support) y controla los cupos.
 */
class AppointmentScheduler
{
    private array $config;

    public function __construct(Settings $settings)
    {
        // Horarios administrables desde el backoffice (Configuración > Agenda de soporte).
        $this->config = $settings->support();
    }

    /**
     * Bloques de un día con su disponibilidad.
     *
     * @return list<array{starts_at:string, ends_at:string, label:string, available:bool, remaining:int}>
     */
    public function slotsFor(CarbonImmutable $date): array
    {
        $date = $date->startOfDay();

        if (! $this->isBookableDay($date)) {
            return [];
        }

        $slots = [];
        $cursor = $date->setTime($this->config['open_hour'], 0);
        $close = $date->setTime($this->config['close_hour'], 0);

        $taken = SupportAppointment::query()
            ->whereIn('status', AppointmentStatus::occupying())
            ->whereBetween('starts_at', [$cursor, $close])
            ->selectRaw('starts_at, count(*) as total')
            ->groupBy('starts_at')
            ->pluck('total', 'starts_at')
            ->mapWithKeys(fn ($total, $start) => [CarbonImmutable::parse($start)->format('Y-m-d H:i') => (int) $total]);

        $minStart = now()->toImmutable()->addHours($this->config['min_notice_hours']);

        while ($cursor->addMinutes($this->config['slot_minutes'])->lte($close)) {
            $end = $cursor->addMinutes($this->config['slot_minutes']);
            $used = $taken->get($cursor->format('Y-m-d H:i'), 0);
            $remaining = max(0, $this->config['slot_capacity'] - $used);

            $slots[] = [
                'starts_at' => $cursor->toIso8601String(),
                'ends_at' => $end->toIso8601String(),
                'label' => $cursor->format('H:i').' – '.$end->format('H:i'),
                'available' => $remaining > 0 && $cursor->gte($minStart),
                'remaining' => $remaining,
            ];

            $cursor = $end;
        }

        return $slots;
    }

    /**
     * @param  array{starts_at:string, topic:string, description:string, order_id?:?int, product_id?:?int}  $data
     *
     * @throws AppointmentException
     */
    public function book(User $user, array $data): SupportAppointment
    {
        $start = CarbonImmutable::parse($data['starts_at'])->setTimezone(config('app.timezone'))->startOfMinute();
        $this->assertValidSlot($start);

        // Bloqueo por bloque horario para que dos reservas simultáneas no superen el cupo.
        return Cache::lock('support-slot:'.$start->timestamp, 10)->block(5, function () use ($user, $data, $start) {
            return DB::transaction(function () use ($user, $data, $start) {
                $occupying = SupportAppointment::query()
                    ->where('starts_at', $start)
                    ->whereIn('status', AppointmentStatus::occupying());

                if ((clone $occupying)->where('user_id', $user->id)->exists()) {
                    throw new AppointmentException('Ya tienes un turno reservado en ese horario.');
                }

                if ($occupying->count() >= $this->config['slot_capacity']) {
                    throw new AppointmentException('Ese horario se acaba de ocupar. Elige otro, por favor.');
                }

                return $user->appointments()->create([
                    'starts_at' => $start,
                    'ends_at' => $start->addMinutes($this->config['slot_minutes']),
                    'topic' => $data['topic'],
                    'description' => $data['description'],
                    'order_id' => $data['order_id'] ?? null,
                    'product_id' => $data['product_id'] ?? null,
                    'status' => AppointmentStatus::Pending,
                ]);
            });
        });
    }

    public function cancelByCustomer(SupportAppointment $appointment): void
    {
        if (! in_array($appointment->status->value, AppointmentStatus::occupying(), true)) {
            throw new AppointmentException('Este turno ya no se puede cancelar.');
        }

        if ($appointment->starts_at->lt(now()->addHours($this->config['min_notice_hours']))) {
            throw new AppointmentException("Los turnos se cancelan con al menos {$this->config['min_notice_hours']} horas de anticipación.");
        }

        $appointment->update(['status' => AppointmentStatus::Cancelled]);
    }

    public function isBookableDay(CarbonImmutable $date): bool
    {
        $today = now()->toImmutable()->startOfDay();

        return in_array($date->isoWeekday(), $this->config['working_days'], true)
            && $date->gte($today)
            && $date->lte($today->addDays($this->config['booking_horizon_days']));
    }

    private function assertValidSlot(CarbonImmutable $start): void
    {
        $day = $start->startOfDay();
        $minutesFromOpen = (int) $day->setTime($this->config['open_hour'], 0)->diffInMinutes($start, false);
        $end = $start->addMinutes($this->config['slot_minutes']);

        $valid = $this->isBookableDay($day)
            && $minutesFromOpen >= 0
            && $minutesFromOpen % $this->config['slot_minutes'] === 0
            && $end->lte($day->setTime($this->config['close_hour'], 0))
            && $start->gte(now()->addHours($this->config['min_notice_hours']));

        if (! $valid) {
            throw new AppointmentException('El horario seleccionado no está disponible.');
        }
    }
}
