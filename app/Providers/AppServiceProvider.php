<?php

namespace App\Providers;

use App\Domain\Payments\Adapters\FinikPaymentGateway;
use App\Domain\Payments\Adapters\NullPaymentGateway;
use App\Domain\Payments\Contracts\PaymentGateway;
use App\Domain\Payments\Services\FinikSigner;
use Illuminate\Support\ServiceProvider;
use RuntimeException;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Провайдер выбирается через PAYMENT_PROVIDER (ARCHITECTURE.md §12,
        // открытый вопрос #1 закрыт в пользу Finik). null оставляет
        // NullPaymentGateway — заглушку для окружений, где ключи ещё не
        // настроены (локальная разработка, CI).
        $this->app->bind(PaymentGateway::class, function () {
            return match (config('services.payment_provider')) {
                'finik' => $this->makeFinikGateway(),
                default => new NullPaymentGateway,
            };
        });
    }

    private function makeFinikGateway(): FinikPaymentGateway
    {
        $config = config('services.finik');

        foreach (['account_id', 'api_key', 'private_key', 'webhook_url'] as $required) {
            if (blank($config[$required] ?? null)) {
                throw new RuntimeException("PAYMENT_PROVIDER=finik, но services.finik.{$required} не задан — проверь .env (FINIK_*).");
            }
        }

        return new FinikPaymentGateway(
            signer: new FinikSigner,
            baseUrl: $config['base_url'],
            accountId: $config['account_id'],
            apiKey: $config['api_key'],
            privateKey: $config['private_key'],
            webhookPublicKey: $config['webhook_public_key'],
            webhookUrl: $config['webhook_url'],
            merchantName: $config['merchant_name'],
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
