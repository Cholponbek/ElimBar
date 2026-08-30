<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Донор может согласиться показать имя вместо замаскированного номера
     * в публичном списке донатов кейса (см. CaseController::donorDisplay).
     * app_public и так имеет полный SELECT/INSERT на donors (RLS-миграция
     * не внесла её в staffOnlyTables), но UPDATE ей никогда не давался —
     * донор обновляет своё имя/согласие при повторном донате
     * (DonationController::store делает updateOrCreate). Грант — только
     * на эти две колонки, не на всю таблицу: донор не должен иметь
     * возможность переписать себе phone/tenant_id даже теоретически.
     */
    public function up(): void
    {
        Schema::table('donors', function (Blueprint $table) {
            $table->boolean('show_name_publicly')->default(false)->after('name');
        });

        DB::statement('GRANT UPDATE (name, show_name_publicly) ON donors TO app_public');
    }

    public function down(): void
    {
        DB::statement('REVOKE UPDATE (name, show_name_publicly) ON donors FROM app_public');

        Schema::table('donors', function (Blueprint $table) {
            $table->dropColumn('show_name_publicly');
        });
    }
};
