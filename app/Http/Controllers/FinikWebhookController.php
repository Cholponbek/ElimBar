<?php

namespace App\Http\Controllers;

use App\Domain\Payments\Contracts\PaymentGateway;
use App\Domain\Payments\Services\WebhookGuard;
use App\Models\Allocation;
use App\Models\Donation;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Finik шлёт вебхук POST только при УСПЕШНОЙ оплате (не для failed) —
 * см. https://www.finik.kg/documentation/web-sdk/. Донат остаётся pending
 * навсегда, если оплата не удалась; повторная попытка донора — новый
 * donation/PaymentId.
 *
 * Пишет через pgsql_staff (роль app_staff), не pgsql_public — у app_public
 * есть только SELECT/INSERT на donations (см. миграцию
 * setup_row_level_security), UPDATE status на pending->completed и
 * INSERT allocation должны идти через доверенный путь. Доверие здесь даёт
 * не сама сессия (запрос анонимный, без донора), а проверенная RSA-подпись
 * Finik — то есть эта проверка обязана происходить раньше любой записи.
 */
class FinikWebhookController extends Controller
{
    public function handle(Request $request, PaymentGateway $gateway, WebhookGuard $guard): Response
    {
        $rawBody = $request->getContent();
        $headers = $request->headers->all();

        if (! $gateway->verifyWebhookSignature($rawBody, $headers)) {
            Log::warning('finik.webhook.invalid_signature', ['ip' => $request->ip()]);

            return response('invalid signature', 400);
        }

        $payload = json_decode($rawBody, true);
        if (! is_array($payload)) {
            return response('invalid payload', 400);
        }

        $externalEventId = $gateway->externalEventId($payload);

        $event = $guard->recordIfNew($gateway->name(), $externalEventId, $payload);
        if ($event === null) {
            // Повторная доставка одного и того же события — тихий no-op.
            return response('', 200);
        }

        $paymentId = $payload['fields']['paymentId'] ?? null;
        $status = strtolower((string) ($payload['status'] ?? ''));

        if (! is_string($paymentId) || $paymentId === '') {
            Log::warning('finik.webhook.missing_payment_id', ['transactionId' => $externalEventId]);

            return response('', 200);
        }

        if (! in_array($status, ['success', 'succeeded'], true)) {
            Log::info('finik.webhook.non_success_status', ['paymentId' => $paymentId, 'status' => $status]);

            return response('', 200);
        }

        $this->confirmDonation($paymentId);

        return response('', 200);
    }

    private function confirmDonation(string $paymentId): void
    {
        DB::connection('pgsql_staff')->transaction(function () use ($paymentId) {
            $donation = Donation::on('pgsql_staff')
                ->where('provider', 'finik')
                ->where('provider_ref', $paymentId)
                ->where('status', 'pending')
                ->lockForUpdate()
                ->first();

            if (! $donation) {
                // Уже обработан (донат перешёл в completed) или PaymentId
                // не наш — не ошибка сама по себе, WebhookGuard выше уже
                // не даст обработать одно и то же дважды, это защита от
                // прочих несостыковок (например, ручное вмешательство).
                Log::warning('finik.webhook.donation_not_found_or_not_pending', ['paymentId' => $paymentId]);

                return;
            }

            $donation->update(['status' => 'completed', 'paid_at' => now()]);

            if ($donation->case_id) {
                Allocation::on('pgsql_staff')->create([
                    'donation_id' => $donation->id,
                    'case_id' => $donation->case_id,
                    'amount_minor' => $donation->amount_minor,
                ]);
            }
        });
    }
}
