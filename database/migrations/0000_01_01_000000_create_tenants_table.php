<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Единственный тенант фазы 1 — фонд «Элим, барсыңбы?!». Таблица существует
     * с первого дня, чтобы tenant_id во всех доменных таблицах был реальным
     * внешним ключом, а не заделом «на будущее».
     */
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->timestamps();
        });

        DB::table('tenants')->insert([
            'id' => 1,
            'slug' => 'elim-barsyngby',
            'name' => 'ОБФ «Элим, барсыңбы?!»',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
