<?php

namespace App\Domain\Payments\DTO;

/**
 * amount_minor — bigint, минорные единицы (тыйын). Никогда float.
 */
final readonly class ChargeRequest
{
    public function __construct(
        public int $amountMinor,
        public string $currency,
        public string $description,
        public string $returnUrl,
        public ?string $donorPhone = null,
    ) {}
}
