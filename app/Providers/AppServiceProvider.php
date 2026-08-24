<?php

namespace App\Providers;

use App\Domain\Payments\Adapters\NullPaymentGateway;
use App\Domain\Payments\Contracts\PaymentGateway;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Провайдер не выбран (ARCHITECTURE.md §12, открытый вопрос #1).
        // Когда выбор сделан — заменить эту строку на реальный адаптер,
        // домен (сервисы, контроллеры, тесты) менять не придётся.
        $this->app->bind(PaymentGateway::class, NullPaymentGateway::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
