<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Донор не авторизован до OTP-логина (см. Donor — не User), поэтому его
 * выбор языка живёт в cookie, не в сессии/БД. donors.locale существует
 * отдельно и пишется только при отправке формы доната — это язык уведомлений
 * донору, не языка интерфейса при просмотре сайта.
 *
 * Должна выполняться ДО HandleInertiaRequests — та делится app()->getLocale()
 * с фронтендом, и переключатель языка в хедере должен увидеть уже применённое
 * значение сразу после редиректа с /locale, а не с задержкой в один запрос.
 */
class SetLocaleFromCookie
{
    public const array SUPPORTED = ['ru', 'ky'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->cookie('elimbar_locale');

        if (is_string($locale) && in_array($locale, self::SUPPORTED, true)) {
            app()->setLocale($locale);
        }

        return $next($request);
    }
}
