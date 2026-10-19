<?php

namespace App\Services\Payments;

final readonly class PaymentInfo
{
    public const APPROVED = 'approved';
    public const REJECTED = 'rejected';
    public const CANCELLED = 'cancelled';
    public const PENDING = 'pending';

    public function __construct(
        public string $id,
        public string $status,         // normalizado: approved | rejected | cancelled | pending
        public string $rawStatus,      // estado tal cual lo entrega la pasarela
        public ?string $externalReference,
        public int $amount,
        public string $currency,
    ) {
    }

    public function isApproved(): bool
    {
        return $this->status === self::APPROVED;
    }

    public function isFinalFailure(): bool
    {
        return in_array($this->status, [self::REJECTED, self::CANCELLED], true);
    }
}
