<?php

namespace App\Domain\Payments\Adapters;

use App\Domain\Payments\Contracts\PaymentGateway;
use App\Domain\Payments\DTO\ChargeRequest;
use App\Domain\Payments\DTO\ChargeResult;
use App\Domain\Payments\DTO\PaymentIntent;
use App\Domain\Payments\Services\FinikSigner;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Finik (acquiring.averspay.kg), https://www.finik.kg/documentation/web-sdk/.
 * Единственный поддерживаемый на сегодня канал — FINIK_QR (хостед-страница
 * с QR-кодом), только KGS. Каждый запрос подписывается RSA-SHA256 нашим
 * приватным ключом (FinikSigner) — публичный ключ той же пары мы заранее
 * передали Finik по почте, они выдали в ответ accountId/apiKey (см.
 * services.finik в config/services.php).
 *
 * POST /v1/payment при успехе отвечает HTTP 302 с Location — это URL
 * хостед-страницы оплаты, а не сырой QR-payload, несмотря на название
 * CardType. Поэтому PaymentIntent::redirectUrl заполняется, а не qrPayload.
 * allow_redirects отключён явно — иначе HTTP-клиент скачает HTML со
 * страницы оплаты вместо чтения заголовка Location.
 */
class FinikPaymentGateway implements PaymentGateway
{
    public function __construct(
        private readonly FinikSigner $signer,
        private readonly string $baseUrl,
        private readonly string $accountId,
        private readonly string $apiKey,
        private readonly string $privateKey,
        private readonly ?string $webhookPublicKey,
        private readonly string $webhookUrl,
        private readonly string $merchantName,
    ) {}

    public function initiate(ChargeRequest $request): PaymentIntent
    {
        if ($request->currency !== 'KGS') {
            throw new RuntimeException("Finik FINIK_QR поддерживает только KGS, получено: {$request->currency}");
        }

        $paymentId = $request->reference;
        $timestamp = (string) (int) round(microtime(true) * 1000);

        $body = [
            'Amount' => intdiv($request->amountMinor, 100),
            'CardType' => 'FINIK_QR',
            'PaymentId' => $paymentId,
            'RedirectUrl' => $request->returnUrl,
            'Data' => array_filter([
                'accountId' => $this->accountId,
                'name_en' => $this->merchantName,
                'webhookUrl' => $this->webhookUrl,
                'description' => $request->description,
            ], fn ($value) => $value !== null && $value !== ''),
        ];

        $path = '/v1/payment';
        $host = $this->host();
        $apiHeaders = ['x-api-key' => $this->apiKey, 'x-api-timestamp' => $timestamp];

        $signature = $this->signer->sign(
            $this->signer->canonicalString('POST', $path, $host, $apiHeaders, $body),
            $this->privateKey,
        );

        $response = Http::withHeaders([
            ...$apiHeaders,
            'signature' => $signature,
        ])
            ->withOptions(['allow_redirects' => false])
            ->post($this->baseUrl.$path, $body);

        if ($response->status() !== 302) {
            throw new RuntimeException(
                "Finik: ожидался 302 от POST {$path}, получено {$response->status()}: {$response->body()}",
            );
        }

        $location = $response->header('Location');
        if (! $location) {
            throw new RuntimeException('Finik: ответ 302 без заголовка Location.');
        }

        return new PaymentIntent(providerRef: $paymentId, redirectUrl: $location);
    }

    /**
     * Finik Web SDK (FINIK_QR) — одноразовые платежи через хостед-страницу
     * с QR. Токенизация/рекуррентное списание документацией, полученной от
     * Finik, не описаны — реализовать по факту получения соответствующей
     * спецификации, не раньше.
     */
    public function chargeToken(string $paymentTokenRef, int $amountMinor, string $currency): ChargeResult
    {
        throw new RuntimeException('FinikPaymentGateway: рекуррентные списания не описаны в полученной документации Finik Web SDK.');
    }

    public function verifyWebhookSignature(string $rawBody, array $headers): bool
    {
        if ($this->webhookPublicKey === null) {
            return false;
        }

        $signature = $this->headerValue($headers, 'signature');
        if ($signature === null) {
            return false;
        }

        $apiHeaders = [];
        foreach ($headers as $key => $value) {
            if (str_starts_with(strtolower($key), 'x-api-')) {
                $apiHeaders[$key] = is_array($value) ? ($value[0] ?? '') : $value;
            }
        }

        $body = json_decode($rawBody, true);
        if (! is_array($body)) {
            $body = [];
        }

        // Хост здесь — наш собственный домен (webhookUrl), не Finik: это
        // ОНИ подписывали запрос на конкретный webhookUrl, который мы им
        // передали при создании платежа. host() (для initiate()) указывает
        // на API-хост Finik и здесь не подходит.
        $webhookPath = parse_url($this->webhookUrl, PHP_URL_PATH) ?: '/';
        $webhookHost = (string) parse_url($this->webhookUrl, PHP_URL_HOST);

        $data = $this->signer->canonicalString('POST', $webhookPath, $webhookHost, $apiHeaders, $body);

        return $this->signer->verify($data, $signature, $this->webhookPublicKey);
    }

    public function externalEventId(array $payload): string
    {
        $id = $payload['transactionId'] ?? null;
        if (! is_string($id) || $id === '') {
            throw new RuntimeException('Finik webhook payload не содержит transactionId.');
        }

        return $id;
    }

    public function name(): string
    {
        return 'finik';
    }

    private function host(): string
    {
        return (string) parse_url($this->baseUrl, PHP_URL_HOST);
    }

    /** @param array<string, string|array<int, string>> $headers */
    private function headerValue(array $headers, string $name): ?string
    {
        foreach ($headers as $key => $value) {
            if (strtolower($key) === $name) {
                return is_array($value) ? ($value[0] ?? null) : $value;
            }
        }

        return null;
    }
}
