<?php

use App\Models\SiteSetting;
use App\Support\TenantContext;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * site_settings — синглтон (см. миграцию), строка засеяна прямо в
 * миграции. app_public получает только SELECT — "О нас"/"Контакты"
 * редактируются исключительно из Filament (app_staff), донор не должен
 * иметь возможности их менять даже теоретически.
 */
it('lets app_public read site_settings but never write to it', function () {
    TenantContext::set(1, DB::connection('pgsql_public'));

    $row = DB::connection('pgsql_public')->table('site_settings')->first();
    expect($row)->not->toBeNull();

    expect(fn () => DB::connection('pgsql_public')->table('site_settings')
        ->where('id', $row->id)
        ->update(['contact_phone' => '+996 000 000 000'])
    )->toThrow(QueryException::class, 'permission denied for table site_settings');
});

it('exposes the localized about/contact content to the homepage', function () {
    $response = $this->get('/');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Cases/Index')
        ->has('siteSettings.aboutTitle.ru')
        ->has('siteSettings.contactPhone')
    );
});

it('lets the Filament admin update the singleton row', function () {
    $setting = SiteSetting::current();

    $setting->update(['contact_phone' => '+996 555 111 222']);

    expect($setting->fresh()->contact_phone)->toBe('+996 555 111 222');
});
