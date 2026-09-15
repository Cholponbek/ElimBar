<?php

use App\Http\Middleware\SetLocaleFromCookie;

/**
 * Переключатель языка в хедере (PublicLayout.vue) — cookie, не сессия/БД
 * (донор не авторизован до OTP, см. SetLocaleFromCookie). APP_LOCALE=ky
 * по умолчанию (.env), поэтому "нет cookie" и "cookie=ky" неотличимы по
 * итоговой локали — второй тест проверяет именно то, что cookie реально
 * читается и переключает на ru, а не что дефолт просто совпал.
 */
it('sets the locale cookie on POST /locale', function () {
    $response = $this->post('/locale', ['locale' => 'ru']);

    $response->assertRedirect();
    $response->assertCookie('elimbar_locale', 'ru');
});

it('rejects an unsupported locale', function () {
    $response = $this->post('/locale', ['locale' => 'en']);

    $response->assertSessionHasErrors('locale');
});

it('applies the locale from the cookie to the shared Inertia prop', function () {
    $response = $this->withCookie('elimbar_locale', 'ru')->get('/');

    $response->assertOk();
    expect(app()->getLocale())->toBe('ru');
});

it('ignores a garbage cookie value and falls back to the app default', function () {
    $this->withCookie('elimbar_locale', 'fr')->get('/');

    expect(app()->getLocale())->toBe(config('app.locale'));
});

it('only recognizes ru and ky as supported locales', function () {
    expect(SetLocaleFromCookie::SUPPORTED)->toBe(['ru', 'ky']);
});
