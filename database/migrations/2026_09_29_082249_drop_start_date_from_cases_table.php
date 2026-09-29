<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * start_date убран — «дата начала кейса» теперь всегда просто cases.created_at,
     * отдельное редактируемое поле только вносило путаницу (могло разойтись
     * с реальной датой создания записи). end_date остаётся редактируемым.
     */
    public function up(): void
    {
        Schema::table('cases', function (Blueprint $table) {
            $table->dropColumn('start_date');
        });
    }

    public function down(): void
    {
        Schema::table('cases', function (Blueprint $table) {
            $table->date('start_date')->nullable()->after('allows_zakat');
        });
    }
};
