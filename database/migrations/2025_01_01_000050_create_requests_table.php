<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Заявка от бенефициара/оператора до того, как она станет кейсом.
     * Модель называется App\Models\CaseRequest (не Request — конфликт с
     * Illuminate\Http\Request), таблица — requests.
     *
     * Три статуса из MVP scope: pending -> verified -> rejected.
     * approved-заявка превращается в Case отдельным действием сервиса
     * (CaseRequestService::approve), а не сменой статуса на "approved" —
     * кейс либо есть, либо его нет, третьего состояния не вводим.
     */
    public function up(): void
    {
        Schema::create('requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $table->foreignId('beneficiary_id')->constrained('beneficiaries')->restrictOnDelete();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('category'); // medical|winter_food (MVP: один тип на старте, поле уже общее)
            $table->string('status')->default('pending'); // pending|verified|rejected
            $table->text('description');
            $table->bigInteger('requested_amount_minor')->nullable();
            $table->char('currency', 3)->default('KGS');
            $table->timestamp('verified_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
        });

        DB::statement('ALTER TABLE requests ADD CONSTRAINT requests_amount_non_negative
            CHECK (requested_amount_minor IS NULL OR requested_amount_minor >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('requests');
    }
};
