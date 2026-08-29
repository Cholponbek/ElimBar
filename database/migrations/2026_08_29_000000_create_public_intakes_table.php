<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Заявка, поданная напрямую с сайта, без сотрудника. app_public не
     * имеет прав на beneficiaries (ARCHITECTURE.md §5) — эта таблица не
     * заменяет CaseRequest, а стоит перед ней: контактные данные и
     * описание нужды, которые сотрудник в админке либо превращает в
     * настоящего Beneficiary + CaseRequest (converted_request_id),
     * либо отклоняет. tenant_id и RLS — в отдельной миграции ниже, тем
     * же приёмом, что и revoke_disbursements_from_app_public: старые
     * миграции задним числом не переписываем.
     */
    public function up(): void
    {
        Schema::create('public_intakes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $table->string('full_name');
            $table->string('phone', 20);
            $table->string('category'); // medical|winter_food, как в requests
            $table->text('description');
            $table->bigInteger('requested_amount_minor')->nullable();
            $table->char('currency', 3)->default('KGS');
            $table->string('status')->default('new'); // new|converted|rejected
            $table->foreignId('converted_request_id')->nullable()->constrained('requests')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
        });

        DB::statement('ALTER TABLE public_intakes ADD CONSTRAINT public_intakes_amount_non_negative
            CHECK (requested_amount_minor IS NULL OR requested_amount_minor >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('public_intakes');
    }
};
