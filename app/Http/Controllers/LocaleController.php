<?php

namespace App\Http\Controllers;

use App\Http\Middleware\SetLocaleFromCookie;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

/**
 * Переключатель языка в хедере (см. PublicLayout.vue). Просто cookie на
 * год — см. SetLocaleFromCookie про то, почему не сессия/БД.
 */
class LocaleController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'locale' => ['required', 'string', 'in:'.implode(',', SetLocaleFromCookie::SUPPORTED)],
        ]);

        Cookie::queue('elimbar_locale', $validated['locale'], 60 * 24 * 365);

        return back();
    }
}
