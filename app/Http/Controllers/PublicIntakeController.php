<?php

namespace App\Http\Controllers;

use App\Models\PublicIntake;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Контур A. Пишет через connection pgsql_public (роль app_public), как и
 * DonationController — но не в beneficiaries/requests напрямую: у
 * app_public на них нет прав вообще (ARCHITECTURE.md §5). Сотрудник
 * конвертирует PublicIntake в настоящую заявку через Filament
 * (PublicIntakeResource), контур B.
 */
class PublicIntakeController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Intakes/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'category' => ['required', 'string', 'in:medical,winter_food'],
            'description' => ['required', 'string', 'max:5000'],
            'requested_amount' => ['nullable', 'integer', 'min:1', 'max:1000000'],
        ]);

        PublicIntake::on('pgsql_public')->create([
            'full_name' => $validated['full_name'],
            'phone' => $validated['phone'],
            'category' => $validated['category'],
            'description' => $validated['description'],
            'requested_amount_minor' => isset($validated['requested_amount'])
                ? $validated['requested_amount'] * 100
                : null,
            'currency' => 'KGS',
            'status' => 'new',
        ]);

        return back()->with('success', 'Заявка отправлена. Сотрудник фонда свяжется с вами после проверки.');
    }
}
