<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Синглтон на tenant — "О нас"/"Контакты" на главной странице
     * (Cases/Index.vue), редактируется в Filament (SiteSettingsPage).
     * UNIQUE(tenant_id) — не просто конвенция "одна строка": лишняя
     * вторая строка тихо перестала бы редактироваться из админки
     * (SiteSetting::current() всегда берёт первую).
     *
     * about_title/about_body/contact_address — jsonb {"ky": "...",
     * "ru": "..."} с fallback-паттерном pickLocale(), тем же, что уже
     * используется для cases.public_title/public_story. Контактные
     * телефон/email/соцсети не переводятся — обычные строки.
     *
     * RLS/гранты — тем же приёмом, что и public_intakes (таблица создана
     * позже основной миграции setup_row_level_security, поэтому
     * настраивается сама на себя, а не там).
     */
    public function up(): void
    {
        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $table->jsonb('about_title')->nullable();
            $table->jsonb('about_body')->nullable();
            $table->jsonb('contact_address')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_instagram')->nullable();
            $table->string('contact_facebook')->nullable();
            $table->string('contact_whatsapp')->nullable();
            $table->timestamps();

            $table->unique('tenant_id');
        });

        DB::statement('ALTER TABLE site_settings ENABLE ROW LEVEL SECURITY');
        DB::statement('ALTER TABLE site_settings FORCE ROW LEVEL SECURITY');

        DB::statement('DROP POLICY IF EXISTS tenant_isolation ON site_settings');
        DB::statement(<<<'SQL'
            CREATE POLICY tenant_isolation ON site_settings
                USING (tenant_id = current_setting('app.tenant_id', true)::bigint)
                WITH CHECK (tenant_id = current_setting('app.tenant_id', true)::bigint)
        SQL);

        DB::statement('GRANT SELECT, UPDATE ON site_settings TO app_staff');
        DB::statement('GRANT SELECT ON site_settings TO app_public');

        // Плейсхолдер-строка прямо здесь, не в отдельном сидере: сидеры не
        // запускаются автоматически при деплое (entrypoint.sh делает
        // только migrate --force, см. docker/php/entrypoint.sh) — без
        // строки тут "О нас"/"Контакты" были бы пустыми на любом свежем
        // окружении, пока кто-то вручную не зайдёт и не заполнит их в
        // Filament. Реальный текст заменяется там же, через админку.
        DB::table('site_settings')->insert([
            'tenant_id' => 1,
            'about_title' => json_encode(['ky' => 'Биз жөнүндө', 'ru' => 'О нас']),
            'about_body' => json_encode([
                'ky' => 'Бул жерге фонд жөнүндө маалымат кийинчерээк толтурулат. Азырынча — үлгү текст.',
                'ru' => 'Здесь появится информация о фонде. Пока это шаблонный текст — заполним позже.',
            ]),
            'contact_address' => json_encode(['ky' => 'Бишкек шаары, Кыргыз Республикасы', 'ru' => 'г. Бишкек, Кыргызская Республика']),
            'contact_phone' => '+996 700 000 000',
            'contact_email' => 'info@elimbar.kg',
            'contact_instagram' => null,
            'contact_facebook' => null,
            'contact_whatsapp' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::statement('DROP POLICY IF EXISTS tenant_isolation ON site_settings');
        Schema::dropIfExists('site_settings');
    }
};
