<?php

declare(strict_types=1);

namespace App\Support\Payments;

final readonly class CheckoutSession
{
    public function __construct(
        public string $reference,
        public string $redirectUrl,
    ) {}
}
