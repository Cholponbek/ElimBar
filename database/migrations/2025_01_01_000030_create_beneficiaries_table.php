<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Полностью приватная таблица. Публичная роль (app_public, см.
     * 2025_01_01_000900_setup_row_level_security) не получает на неё вообще
     * никаких прав — не «фильтруется», а физически недоступна.
     */
    public function up(): void
    {
        Schema::create('beneficiaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $table->string('full_name');
            $table->string('phone', 20)->nullable();
            $table->string('document_number')->nullable();
            $table->string('city')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('beneficiaries');
    }
};
