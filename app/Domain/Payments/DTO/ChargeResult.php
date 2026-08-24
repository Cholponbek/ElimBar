<?php

namespace App\Domain\Payments\DTO;

final readonly class ChargeResult
{
    public function __construct(
        public bool $success,
        public string $providerRef,
        public ?string $failureReason = null,
    ) {}
}
