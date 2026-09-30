<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Редизайн блока "О фонде"/"Контакты" на главной (см. Cases/Index.vue):
     * блок "О фонде" получает три редактируемых подраздела (волонтёры,
     * ящики, соц-магазин), а "Контакты" отделяется в свой блок с более
     * подробным набором полей — два телефона с разным назначением, два
     * email и сайт вместо расплывчатого набора соцсетей.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE site_settings RENAME COLUMN contact_phone TO contact_reception_phone');

        DB::statement('ALTER TABLE site_settings ADD COLUMN contact_partnership_phone VARCHAR(30)');
        DB::statement('ALTER TABLE site_settings ADD COLUMN contact_email_secondary VARCHAR(255)');
        DB::statement('ALTER TABLE site_settings ADD COLUMN contact_website VARCHAR(255)');

        DB::statement('ALTER TABLE site_settings ADD COLUMN about_volunteers_body JSONB');
        DB::statement('ALTER TABLE site_settings ADD COLUMN about_boxes_body JSONB');
        DB::statement('ALTER TABLE site_settings ADD COLUMN about_shop_body JSONB');

        DB::statement('ALTER TABLE site_settings DROP COLUMN contact_facebook');
        DB::statement('ALTER TABLE site_settings DROP COLUMN contact_whatsapp');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE site_settings ADD COLUMN contact_facebook VARCHAR(255)');
        DB::statement('ALTER TABLE site_settings ADD COLUMN contact_whatsapp VARCHAR(255)');

        DB::statement('ALTER TABLE site_settings DROP COLUMN about_shop_body');
        DB::statement('ALTER TABLE site_settings DROP COLUMN about_boxes_body');
        DB::statement('ALTER TABLE site_settings DROP COLUMN about_volunteers_body');

        DB::statement('ALTER TABLE site_settings DROP COLUMN contact_website');
        DB::statement('ALTER TABLE site_settings DROP COLUMN contact_email_secondary');
        DB::statement('ALTER TABLE site_settings DROP COLUMN contact_partnership_phone');

        DB::statement('ALTER TABLE site_settings RENAME COLUMN contact_reception_phone TO contact_phone');
    }
};
