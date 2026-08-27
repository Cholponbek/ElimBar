<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * disbursements пропущен в staffOnlyTables в setup_row_level_security
     * — app_public получил там SELECT/INSERT наравне с donations/
     * allocations. Выплата — действие сотрудника фонда (contour B,
     * Filament), донор не должен иметь возможность писать в эту таблицу
     * ни при каких обстоятельствах. Обнаружено при подготовке публичной
     * формы доната: перечитывал список грантов и увидел лишнее.
     */
    public function up(): void
    {
        DB::statement('REVOKE ALL ON disbursements FROM app_public');
    }

    public function down(): void
    {
        DB::statement('GRANT SELECT, INSERT ON disbursements TO app_public');
    }
};
