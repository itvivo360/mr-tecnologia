<?php

namespace App\Services\Payments;

final readonly class CheckoutSession
{
    public function __construct(
        public string $reference,
        public string $redirectUrl,
    ) {
    }
}
