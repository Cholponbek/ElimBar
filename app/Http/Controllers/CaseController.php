<?php

namespace App\Http\Controllers;

use App\Models\Allocation;
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

        // Только сумма и дата — донор не выбирает публичность, поэтому
        // телефон здесь не показываем никому, ни при каких условиях.
        $recentDonations = Allocation::on('pgsql_public')
            ->where('case_id', $case->id)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get(['amount_minor', 'created_at'])
            ->map(fn (Allocation $allocation) => [
                'amount_minor' => $allocation->amount_minor,
                'created_at' => $allocation->created_at,
            ]);

        return Inertia::render('Cases/Show', [
            'case' => $this->presentCase($case),
            'recentDonations' => $recentDonations,
        ]);
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
