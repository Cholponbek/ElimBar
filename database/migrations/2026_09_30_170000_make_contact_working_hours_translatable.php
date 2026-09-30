<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * "График работы" оказался единственным полем контактов, которое не
     * переводится по языкам, хотя должно — как contact_address. Меняем
     * varchar на jsonb {"ky": "...", "ru": "...", "en": "..."}, старое
     * значение переносим в "ru" (было заполнено на русском), ky/en
     * останутся пустыми — заполнит админ.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE site_settings ADD COLUMN contact_working_hours_new JSONB');
        DB::statement(<<<'SQL'
            UPDATE site_settings
            SET contact_working_hours_new = jsonb_build_object('ru', contact_working_hours)
            WHERE contact_working_hours IS NOT NULL
        SQL);
        DB::statement('ALTER TABLE site_settings DROP COLUMN contact_working_hours');
        DB::statement('ALTER TABLE site_settings RENAME COLUMN contact_working_hours_new TO contact_working_hours');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE site_settings ADD COLUMN contact_working_hours_old VARCHAR(255)');
        DB::statement(<<<'SQL'
            UPDATE site_settings
            SET contact_working_hours_old = contact_working_hours->>'ru'
            WHERE contact_working_hours IS NOT NULL
        SQL);
        DB::statement('ALTER TABLE site_settings DROP COLUMN contact_working_hours');
        DB::statement('ALTER TABLE site_settings RENAME COLUMN contact_working_hours_old TO contact_working_hours');
    }
};
