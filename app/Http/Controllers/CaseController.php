<?php

namespace App\Http\Controllers;

use App\Models\Allocation;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\PublicCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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
            ->get()
            ->map($this->presentCase(...));

        return Inertia::render('Cases/Index', [
            'cases' => $cases,
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

        $donorPhones = Donor::on('pgsql_public')
            ->whereIn('id', $donations->pluck('donor_id'))
            ->get(['id', 'phone'])
            ->keyBy('id')
            ->map(fn (Donor $donor) => $this->maskPhone($donor->phone));

        $recentDonations = $allocations->map(function (Allocation $allocation) use ($donations, $donorPhones) {
            $donorId = $donations->get($allocation->donation_id)?->donor_id;

            return [
                'amount_minor' => $allocation->amount_minor,
                'created_at' => $allocation->created_at,
                'donorPhoneMasked' => $donorId ? $donorPhones->get($donorId) : null,
            ];
        });

        return Inertia::render('Cases/Show', [
            'case' => $this->presentCase($case),
            'recentDonations' => $recentDonations,
        ]);
    }

    private function maskPhone(string $phone): string
    {
        return '•••• '.substr($phone, -3);
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
