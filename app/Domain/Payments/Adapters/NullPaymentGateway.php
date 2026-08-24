<?php

namespace App\Domain\Payments\Adapters;

use App\Domain\Payments\Contracts\PaymentGateway;
use App\Domain\Payments\DTO\ChargeRequest;
use App\Domain\Payments\DTO\ChargeResult;
use App\Domain\Payments\DTO\PaymentIntent;
use RuntimeException;

/**
 * Провайдер не выбран (открытый вопрос фазы 0). Эта заглушка держит
 * PaymentGateway::class резолвящимся в контейнере, чтобы остальной домен
 * (DonationService, вебхуки, тесты идемпотентности) можно было писать и
 * тестировать уже сейчас, не дожидаясь коммерческих условий провайдера.
 * Ничего не делает по-настоящему — бросает исключение на попытку реального
 * списания. Замена — в AppServiceProvider::register(), одна строка bind().
 */
class NullPaymentGateway implements PaymentGateway
{
    public function initiate(ChargeRequest $request): PaymentIntent
    {
        throw new RuntimeException('No payment provider configured yet — see ARCHITECTURE.md open question #1.');
    }

    public function chargeToken(string $paymentTokenRef, int $amountMinor, string $currency): ChargeResult
    {
        throw new RuntimeException('No payment provider configured yet — see ARCHITECTURE.md open question #1.');
    }

    public function verifyWebhookSignature(string $rawBody, array $headers): bool
    {
        return false;
    }

    public function externalEventId(array $payload): string
    {
        throw new RuntimeException('No payment provider configured yet — see ARCHITECTURE.md open question #1.');
    }

    public function name(): string
    {
        return 'null';
    }
}
