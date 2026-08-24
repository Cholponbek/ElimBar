<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Версионированное согласие на раскрытие данных бенефициара, не
        // булево поле "согласился/нет" — юридически нужна версия текста.
        Schema::create('consents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $table->foreignId('beneficiary_id')->constrained('beneficiaries')->restrictOnDelete();
            $table->string('document_version');
            $table->string('scope'); // например: "public_case_story", "photo"
            $table->timestamp('granted_at');
            $table->foreignId('granted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Кто/когда открыл запись бенефициара. Финансовый аудит отдельной
        // таблицы не требует — он уже есть в append-only donations/
        // allocations/disbursements.
        Schema::create('access_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('beneficiary_id')->constrained('beneficiaries')->restrictOnDelete();
            $table->string('action'); // viewed|exported|...
            $table->timestamp('occurred_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('access_logs');
        Schema::dropIfExists('consents');
    }
};
