<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Failed = 'failed';
    case Expired = 'expired';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendiente de pago',
            self::Paid => 'Pagada',
            self::Failed => 'Pago rechazado',
            self::Expired => 'Expirada',
            self::Cancelled => 'Cancelada',
        };
    }

    /** Estados en los que el stock de la orden ya no está reservado. */
    public function releasesStock(): bool
    {
        return in_array($this, [self::Failed, self::Expired, self::Cancelled], true);
    }
}
