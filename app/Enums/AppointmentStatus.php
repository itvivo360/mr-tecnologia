<?php

namespace App\Enums;

enum AppointmentStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendiente',
            self::Confirmed => 'Confirmado',
            self::Completed => 'Atendido',
            self::Cancelled => 'Cancelado',
        };
    }

    /** Estados que ocupan cupo en la agenda. */
    public static function occupying(): array
    {
        return [self::Pending->value, self::Confirmed->value];
    }
}
