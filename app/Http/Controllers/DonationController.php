<?php

namespace App\Http\Controllers;

use App\Domain\Payments\Contracts\PaymentGateway;
use App\Domain\Payments\DTO\ChargeRequest;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\PublicCase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Throwable;

/**
 * Контур A. Донат сначала создаётся 'pending' (роль app_public имеет
 * только SELECT/INSERT на donations, см. setup_row_level_security — сама
 * эта роль физически не может пометить донат completed), затем донора
 * уводит на хостед-страницу Finik. Подтверждает оплату и зачисляет деньги
 * на кейс (создаёт Allocation) только вебхук — FinikWebhookController,
 * через доверенную роль app_staff, после проверки RSA-подписи. Если донор
 * закрыл страницу Finik или отменил оплату — донат так и останется
 * pending навсегда, это ожидаемо и безвредно (деньги не списаны).
 *
 * Пишет строго через connection pgsql_public (роль app_public) — та же
 * граница, что и у чтения в CaseController. Donor/Donation — обычные
 * модели контура B, здесь используются через ->on('pgsql_public'),
 * отдельных Public*-моделей для записи не заводим: колонки не урезаны,
 * различие только в подключении.
 */
class DonationController extends Controller
{
    public function store(Request $request, int $case, PaymentGateway $gateway): RedirectResponse|SymfonyResponse
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', 'max:20'],
            'amount' => ['required', 'integer', 'min:1', 'max:1000000'],
            'name' => ['nullable', 'string', 'max:255'],
            'show_name_publicly' => ['sometimes', 'boolean'],
        ]);

        // Без имени показывать нечего — тихо отключаем флаг, а не 422:
        // чекбокс мог остаться отмеченным с прошлого раза, пока поле имени
        // пустое, и это не повод ронять весь донат.
        $showNamePublicly = ($validated['show_name_publicly'] ?? false) && filled($validated['name'] ?? null);

        $publicCase = PublicCase::query()->where('status', 'active')->findOrFail($case);

        $reference = (string) Str::uuid();
        $amountMinor = $validated['amount'] * 100;

        DB::connection('pgsql_public')->transaction(function () use ($validated, $showNamePublicly, $publicCase, $reference, $amountMinor) {
            // updateOrCreate, не firstOrCreate — донор мог написать имя и
            // дать согласие только сейчас, при повторном донате с того же
            // номера. Каждый донат — это его текущее решение: не отмечен
            // чекбокс в этот раз — значит имя больше не показываем, даже
            // если раньше показывали (согласие не должно молча длиться
            // вечно без нового явного подтверждения).
            $donor = Donor::on('pgsql_public')->updateOrCreate(
                ['phone' => $validated['phone']],
                [
                    'locale' => app()->getLocale(),
                    'name' => $validated['name'] ?? null,
                    'show_name_publicly' => $showNamePublicly,
                ],
            );

            Donation::on('pgsql_public')->create([
                'donor_id' => $donor->id,
                'case_id' => $publicCase->id,
                'amount_minor' => $amountMinor,
                'currency' => 'KGS',
                'fund_type' => 'general',
                'status' => 'pending',
                'provider' => 'finik',
                'provider_ref' => $reference,
            ]);
        });

        // Вызов провайдера — намеренно вне транзакции выше: сетевой запрос
        // не должен держать открытой транзакцию к БД.
        try {
            $intent = $gateway->initiate(new ChargeRequest(
                reference: $reference,
                amountMinor: $amountMinor,
                currency: 'KGS',
                description: "Донат на кейс #{$publicCase->id}",
                returnUrl: route('cases.show', ['case' => $publicCase->id]),
                donorPhone: $validated['phone'],
            ));
        } catch (Throwable $e) {
            Log::error('donation.initiate_failed', ['reference' => $reference, 'message' => $e->getMessage()]);

            return back()->with('error', 'Не удалось начать оплату. Попробуйте ещё раз через пару минут.');
        }

        if (! $intent->redirectUrl) {
            Log::error('donation.initiate_no_redirect', ['reference' => $reference]);

            return back()->with('error', 'Не удалось начать оплату. Попробуйте ещё раз через пару минут.');
        }

        // Inertia::location(), не redirect()->away(): обычный 3xx-редирект
        // Inertia попытается забрать XHR'ом и упадёт на чужом домене —
        // это отдаёт 409 с X-Inertia-Location, по которому клиент Inertia
        // сам делает window.location, полноценный уход из SPA на Finik.
        return Inertia::location($intent->redirectUrl);
    }
}
