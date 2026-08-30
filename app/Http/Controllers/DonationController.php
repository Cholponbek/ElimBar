<?php

namespace App\Http\Controllers;

use App\Models\Allocation;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\PublicCase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Контур A. Провайдер не выбран (ARCHITECTURE.md §12, открытый вопрос #1)
 * — это фейковая оплата для демо: донат помечается completed сразу,
 * без реального списания денег. Настоящий провайдер подключается через
 * PaymentGateway (см. app/Domain/Payments), не здесь; когда он появится,
 * этот метод станет вызывать initiate()/webhook вместо прямого create().
 *
 * Пишет строго через connection pgsql_public (роль app_public) — та же
 * граница, что и у чтения в CaseController. Donor/Donation/Allocation —
 * обычные модели контура B, здесь используются через ->on('pgsql_public'),
 * отдельных Public*-моделей для записи не заводим: колонки не урезаны,
 * различие только в подключении.
 */
class DonationController extends Controller
{
    public function store(Request $request, int $case): RedirectResponse
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

        DB::connection('pgsql_public')->transaction(function () use ($validated, $showNamePublicly, $publicCase) {
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

            $donation = Donation::on('pgsql_public')->create([
                'donor_id' => $donor->id,
                'amount_minor' => $validated['amount'] * 100,
                'currency' => 'KGS',
                'fund_type' => 'general',
                'status' => 'completed',
                'provider' => 'fake',
                'provider_ref' => (string) Str::uuid(),
                'paid_at' => now(),
            ]);

            Allocation::on('pgsql_public')->create([
                'donation_id' => $donation->id,
                'case_id' => $publicCase->id,
                'amount_minor' => $donation->amount_minor,
            ]);
        });

        return back()->with('success', 'Спасибо! Донат зачислен на этот кейс.');
    }
}
