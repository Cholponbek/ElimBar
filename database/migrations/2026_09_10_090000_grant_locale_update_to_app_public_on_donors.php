<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * 2026_08_30_090000_add_show_name_publicly_to_donors_table гранул
     * UPDATE (name, show_name_publicly) на donors для app_public, но
     * забыл locale и updated_at — а DonationController::store() при
     * каждом updateOrCreate() обновляет locale ('locale' =>
     * app()->getLocale()), и Eloquent сам трогает updated_at на любом
     * update()/save() (Donor не отключает $timestamps). Postgres
     * проверяет привилегии по каждой колонке в UPDATE-запросе: если хоть
     * одна не разрешена — падает весь запрос с "permission denied for
     * table donors", а не только по недостающей колонке. Не проявлялось
     * до сих пор, пока не пришёл повторный донат с уже существующим
     * номером телефона (UPDATE-ветка updateOrCreate; при первом донате
     * отрабатывает INSERT, на который эта проблема не распространяется).
     */
    public function up(): void
    {
        DB::statement('GRANT UPDATE (locale, updated_at) ON donors TO app_public');
    }

    public function down(): void
    {
        DB::statement('REVOKE UPDATE (locale, updated_at) ON donors FROM app_public');
    }
};
