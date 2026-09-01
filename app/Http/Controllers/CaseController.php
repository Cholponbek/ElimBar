<?php

namespace App\Http\Controllers;

use App\Models\Allocation;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\PublicCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Контур A (донор). Читает исключительно через PublicCase (connection
 * pgsql_public, view cases_public) — см. ARCHITECTURE.md §5. Никогда не
 * подключать сюда FundCase/Beneficiary: у роли app_public на них нет прав
 * на уровне Postgres, запрос просто упадёт вместо того, чтобы утечь ФИО.
 */
class CaseController extends Controller
{
    public function index(): Response
    {
        $cases = PublicCase::query()
            ->where('status', 'active')
            ->orderByDesc('created_at')
            ->get();

        // Число донатов на кейс — как в карточках GoFundMe ("X donations"),
        // только настоящее: считаем аллокации, а не выдумываем. sum()/count()
        // ниже на уже загруженной коллекции — вторая БД-поездка не нужна.
        $donationsPerCase = Allocation::on('pgsql_public')
            ->whereIn('case_id', $cases->pluck('id'))
            ->selectRaw('case_id, count(*) as donations_count')
            ->groupBy('case_id')
            ->pluck('donations_count', 'case_id');

        $presented = $cases->map(fn (PublicCase $case) => [
            ...$this->presentCase($case),
            'donationsCount' => (int) $donationsPerCase->get($case->id, 0),
        ]);

        $this->shareMeta([
            'description' => 'Каждый сом привязан к конкретному кейсу — публичный отчёт собирается автоматически.',
        ]);

        return Inertia::render('Cases/Index', [
            'cases' => $presented,
            'stats' => [
                'activeCases' => $cases->count(),
                'raisedMinor' => (int) $cases->sum('allocated_minor'),
                'donationsCount' => (int) $donationsPerCase->sum(),
            ],
        ]);
    }

    public function show(int $case): Response
    {
        $case = PublicCase::query()->where('status', 'active')->findOrFail($case);

        // Телефон донора показываем замаскированным (только последние 3
        // цифры) — не полностью анонимно, но и не так, чтобы кто-то мог
        // прочитать чужой номер целиком с публичной страницы. Отдельные
        // запросы на каждую модель через pgsql_public, не ->with() —
        // eager-load не даёт гарантии, каким connection пойдёт связанная
        // модель, а здесь это должно быть явно app_public, не app_staff.
        $allocations = Allocation::on('pgsql_public')
            ->where('case_id', $case->id)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get(['donation_id', 'amount_minor', 'created_at']);

        $donations = Donation::on('pgsql_public')
            ->whereIn('id', $allocations->pluck('donation_id'))
            ->get(['id', 'donor_id'])
            ->keyBy('id');

        $donors = Donor::on('pgsql_public')
            ->whereIn('id', $donations->pluck('donor_id'))
            ->get(['id', 'phone', 'name', 'show_name_publicly'])
            ->keyBy('id')
            ->map(fn (Donor $donor) => $donor->show_name_publicly && filled($donor->name)
                ? $donor->name
                : $this->maskPhone($donor->phone));

        $recentDonations = $allocations->map(function (Allocation $allocation) use ($donations, $donors) {
            $donorId = $donations->get($allocation->donation_id)?->donor_id;

            return [
                'amount_minor' => $allocation->amount_minor,
                'created_at' => $allocation->created_at,
                'donorDisplay' => $donorId ? $donors->get($donorId) : null,
            ];
        });

        $presented = $this->presentCase($case);

        $this->shareMeta([
            'type' => 'article',
            'title' => $this->pickLocale($case->public_title, app()->getLocale()),
            'description' => Str::limit($this->pickLocale($case->public_story, app()->getLocale()) ?: 'Помогите собрать на этот кейс — каждый сом виден в публичном отчёте.', 160),
            'image' => $presented['photoUrl'],
            'url' => url()->current(),
        ]);

        return Inertia::render('Cases/Show', [
            'case' => $presented,
            'recentDonations' => $recentDonations,
        ]);
    }

    /**
     * "•••• 367" читалось как случайный набор символов, было непонятно,
     * что это вообще номер телефона. +996 XXX ****XX (код страны и
     * оператор открыты, абонентский номер замаскирован кроме двух
     * последних цифр) — сразу видно формат, но узнать конкретный номер
     * по-прежнему нельзя.
     */
    private function maskPhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone);

        if (str_starts_with($digits, '996') && strlen($digits) === 12) {
            $operator = substr($digits, 3, 3);
            $lastTwo = substr($digits, -2);

            return "+996 {$operator} ****{$lastTwo}";
        }

        return '**** '.substr($digits, -2);
    }

    /**
     * Open Graph/Twitter Card теги для превью при шаринге (Instagram Stories,
     * Telegram, WhatsApp, Facebook — все читают og:* из самого первого,
     * ещё не гидрированного HTML-ответа). Inertia не рендерит SSR в этом
     * проекте, поэтому Vue-компонент <Head> здесь бесполезен: краулеры не
     * выполняют JS и никогда не увидят то, что он допишет в <head> после
     * гидратации. View::share кладёт данные в общий пул переменных Blade,
     * который resources/views/app.blade.php читает при каждом полном
     * рендере страницы (и Inertia, и обычный первый заход используют один
     * и тот же root-шаблон).
     *
     * @param  array{type?: string, title?: string, description?: string, image?: ?string, url?: string}  $meta
     */
    private function shareMeta(array $meta): void
    {
        View::share('meta', $meta);
    }

    private function pickLocale(?array $value, string $locale, string $fallback = 'ru'): ?string
    {
        if (! $value) {
            return null;
        }

        return $value[$locale] ?? $value[$fallback] ?? reset($value) ?: null;
    }

    private function presentCase(PublicCase $case): array
    {
        return [
            'id' => $case->id,
            'category' => $case->category,
            'title' => $case->public_title,
            'story' => $case->public_story,
            'photoUrl' => $case->public_photo_path
                ? Storage::disk('public')->url($case->public_photo_path)
                : null,
            'currency' => $case->currency,
            'budget_minor' => $case->budget_minor,
            'allocated_minor' => $case->allocated_minor,
            'disbursed_minor' => $case->disbursed_minor,
            'allows_zakat' => $case->allows_zakat,
        ];
    }
}
