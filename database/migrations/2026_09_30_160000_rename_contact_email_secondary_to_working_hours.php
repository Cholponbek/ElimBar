<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Поле №5 блока "Контакты фонда" по факту оказалось не вторым email,
     * а графиком работы (заказчик поправил после первой версии) — просто
     * переименовываем колонку, второй email не понадобился.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE site_settings RENAME COLUMN contact_email_secondary TO contact_working_hours');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE site_settings RENAME COLUMN contact_working_hours TO contact_email_secondary');
    }
};
