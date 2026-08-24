<?php

namespace App\Domain\Payments\Contracts;

use App\Domain\Payments\DTO\ChargeRequest;
use App\Domain\Payments\DTO\ChargeResult;
use App\Domain\Payments\DTO\PaymentIntent;

/**
 * Единственная точка контакта домена с провайдером эквайринга. Провайдер
 * на момент написания не выбран (открытый вопрос фазы 0, см.
 * ARCHITECTURE.md §12) — ELQR, карточный эквайринг и Apple/Google Pay
 * реализуются как отдельные адаптеры за этим интерфейсом. Доменный слой
 * (DonationService, SubscriptionChargeService, вебхук-обработчики) не
 * знает о специфике конкретного провайдера.
 */
interface PaymentGateway
{
    /**
     * Инициировать одноразовый платёж (донат). Возвращает то, что нужно
     * фронтенду для завершения оплаты (redirect URL, QR-payload и т.п.) —
     * форма PaymentIntent намеренно провайдеро-независима.
     */
    public function initiate(ChargeRequest $request): PaymentIntent;

    /**
     * Списать по ранее сохранённому payment_token_ref (рекуррент). Ни один
     * адаптер не должен принимать/хранить сырые данные карты — только
     * ссылку на токен у провайдера.
     */
    public function chargeToken(string $paymentTokenRef, int $amountMinor, string $currency): ChargeResult;

    /**
     * Проверить подлинность вебхука (подпись/секрет) до того, как payload
     * попадёт в payment_events. Идемпотентность обеспечивает не адаптер, а
     * Domain\Payments\Services\WebhookGuard — на уровне UNIQUE(provider,
     * external_event_id) в БД.
     */
    public function verifyWebhookSignature(string $rawBody, array $headers): bool;

    /** Провайдеро-специфичный идентификатор события — ключ идемпотентности. */
    public function externalEventId(array $payload): string;

    public function name(): string;
}
