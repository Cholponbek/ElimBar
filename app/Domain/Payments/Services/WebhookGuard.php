<?php

namespace App\Domain\Payments\Services;

use App\Models\PaymentEvent;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Провайдер пришлёт один и тот же колбэк дважды — это норма, а не сбой.
 * INSERT сначала, обработка payload — только если вставка реально
 * произошла (первая доставка). Повторная доставка — тихий, идемпотентный
 * no-op. UNIQUE(provider, external_event_id) в БД — источник истины, не
 * эта проверка (см. миграцию create_payment_events_table).
 */
class WebhookGuard
{
    /**
     * @return PaymentEvent|null null если это уже обработанное событие
     *                           (повторная доставка) — обработчик обязан
     *                           тогда молча вернуть 200 и ничего не делать.
     */
    public function recordIfNew(string $provider, string $externalEventId, array $payload): ?PaymentEvent
    {
        try {
            // Postgres помечает всю текущую транзакцию как aborted при любой
            // ошибке внутри неё, даже если исключение поймано в PHP. Если
            // recordIfNew() вызывается внутри DB::transaction() (см.
            // конвенцию "любая мутация денег — внутри транзакции"),
            // непойманный дубликат отравил бы все последующие запросы
            // вызывающего кода. DB::transaction() здесь создаёт SAVEPOINT
            // (Laravel умеет вкладывать транзакции), поэтому откатывается
            // только эта вставка, а не вся внешняя транзакция.
            return DB::transaction(fn () => PaymentEvent::create([
                'provider' => $provider,
                'external_event_id' => $externalEventId,
                'payload' => $payload,
                'received_at' => now(),
            ]));
        } catch (QueryException $e) {
            if ($this->isUniqueViolation($e)) {
                return null;
            }

            throw $e;
        }
    }

    private function isUniqueViolation(QueryException $e): bool
    {
        return $e->getCode() === '23505';
    }
}
