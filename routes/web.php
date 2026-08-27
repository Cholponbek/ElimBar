<?php

use App\Http\Controllers\CaseController;
use App\Http\Controllers\DonationController;
use Illuminate\Support\Facades\Route;

// Контур A (донор). Контур B живёт под /admin (Filament, зарегистрирован
// отдельным panel-провайдером, не здесь).
Route::get('/', [CaseController::class, 'index'])->name('cases.index');
Route::get('/cases/{case}', [CaseController::class, 'show'])->name('cases.show');
Route::post('/cases/{case}/donate', [DonationController::class, 'store'])->name('donations.store');
