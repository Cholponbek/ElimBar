<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Модель называется App\Models\FundCase (не Case — `case` зарезервировано
     * языком под enum-выражения с PHP 8.1), таблица — cases.
     *
     * budget_minor / allocated_minor / disbursed_minor обновляются только
     * триггерами из allocations/disbursements (см. 2025_01_01_000090).
     * Приложение их никогда не пишет напрямую.
     */
    public function up(): void
    {
        Schema::create('cases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $table->foreignId('request_id')->nullable()->constrained('requests')->nullOnDelete();
            $table->foreignId('campaign_id')->nullable()->constrained('campaigns')->nullOnDelete();
            $table->foreignId('beneficiary_id')->constrained('beneficiaries')->restrictOnDelete();

            $table->string('category');
            $table->string('status')->default('draft'); // draft|active|closed

            // Публичный слой (видим роли app_public через cases_public view).
            $table->jsonb('public_title');
            $table->jsonb('public_story')->nullable();
            $table->foreignId('public_photo_id')->nullable()->constrained('proofs')->nullOnDelete();

            $table->char('currency', 3)->default('KGS');
            $table->bigInteger('budget_minor')->default(0);
            $table->bigInteger('allocated_minor')->default(0);
            $table->bigInteger('disbursed_minor')->default(0);
            $table->boolean('allows_zakat')->default(false);

            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });

        DB::statement('ALTER TABLE cases ADD CONSTRAINT cases_budget_non_negative
            CHECK (budget_minor >= 0)');
        DB::statement('ALTER TABLE cases ADD CONSTRAINT cases_allocated_within_bounds
            CHECK (allocated_minor >= 0)');
        DB::statement('ALTER TABLE cases ADD CONSTRAINT cases_disbursed_within_budget
            CHECK (disbursed_minor >= 0 AND disbursed_minor <= budget_minor)');
    }

    public function down(): void
    {
        Schema::dropIfExists('cases');
    }
};
