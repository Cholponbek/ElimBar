<?php

use App\Http\Controllers\CaseController;
use App\Http\Controllers\DonationController;
use App\Http\Controllers\FinikWebhookController;
use App\Http\Controllers\PublicIntakeController;
use Illuminate\Support\Facades\Route;

// Контур A (донор). Контур B живёт под /admin (Filament, зарегистрирован
// отдельным panel-провайдером, не здесь).
Route::get('/', [CaseController::class, 'index'])->name('cases.index');
Route::get('/cases/{case}', [CaseController::class, 'show'])->name('cases.show');
Route::post('/cases/{case}/donate', [DonationController::class, 'store'])->name('donations.store');
Route::get('/help', [PublicIntakeController::class, 'create'])->name('intakes.create');
Route::post('/help', [PublicIntakeController::class, 'store'])->name('intakes.store');

// Server-to-server, без CSRF-токена (см. bootstrap/app.php validateCsrfTokens
// except) — подлинность проверяется RSA-подписью Finik, не сессией/куками.
Route::post('/webhooks/finik', [FinikWebhookController::class, 'handle'])->name('webhooks.finik');
