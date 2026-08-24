<?php

namespace App\Domain\Payments\DTO;

/**
 * То, что нужно фронтенду для завершения оплаты. redirectUrl — для карт/
 * Apple/Google Pay, qrPayload — для ELQR. Оба nullable: конкретный адаптер
 * заполняет то, что уместно для своего канала.
 */
final readonly class PaymentIntent
{
    public function __construct(
        public string $providerRef,
        public ?string $redirectUrl = null,
        public ?string $qrPayload = null,
    ) {}
}
