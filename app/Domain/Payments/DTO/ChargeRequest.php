<?php

namespace App\Domain\Payments\DTO;

/**
 * amount_minor — bigint, минорные единицы (тыйын). Никогда float.
 *
 * reference — наш собственный ключ идемпотентности (Finik: PaymentId),
 * генерируется вызывающим кодом ДО initiate(), не адаптером: donations.
 * provider_ref пишется в той же INSERT-транзакции, что создаёт сам
 * донат — роль app_public не имеет UPDATE на donations (см.
 * setup_row_level_security), поэтому подставить provider_ref задним
 * числом после ответа провайдера невозможно.
 */
final readonly class ChargeRequest
{
    public function __construct(
        public string $reference,
        public int $amountMinor,
        public string $currency,
        public string $description,
        public string $returnUrl,
        public ?string $donorPhone = null,
    ) {}
}
