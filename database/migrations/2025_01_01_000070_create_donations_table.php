<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Append-only. Исправление ошибки — новая строка со reversal_of_id и
     * отрицательной amount_minor, не UPDATE. UPDATE на amount_minor/
     * allocated_minor/fund_type запрещён отдельным REVOKE-триггером ниже —
     * приложение обновляет только неденежные поля через API Postgres,
     * но проще и надёжнее запретить UPDATE денежных колонок совсем.
     */
    public function up(): void
    {
        Schema::create('donations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $table->foreignId('donor_id')->constrained('donors')->restrictOnDelete();
            $table->foreignId('campaign_id')->nullable()->constrained('campaigns')->nullOnDelete();
            $table->foreignId('case_id')->nullable()->constrained('cases')->nullOnDelete();
            $table->foreignId('subscription_id')->nullable(); // FK добавлен после создания subscriptions

            $table->bigInteger('amount_minor');
            $table->char('currency', 3)->default('KGS');
            $table->bigInteger('provider_fee_minor')->default(0);
            $table->string('fund_type')->default('general'); // general|zakat|sadaqah|waqf
            $table->bigInteger('allocated_minor')->default(0);

            $table->string('status')->default('pending'); // pending|completed|failed|reversed
            $table->string('provider')->nullable();
            $table->string('provider_ref')->nullable();
            $table->timestamp('paid_at')->nullable();

            $table->foreignId('reversal_of_id')->nullable()->constrained('donations')->restrictOnDelete();

            $table->timestamps();
        });

        // Положительный донат ИЛИ отрицательное сторно, третьего не дано.
        DB::statement('ALTER TABLE donations ADD CONSTRAINT donations_amount_sign
            CHECK (
                (reversal_of_id IS NULL AND amount_minor > 0)
                OR (reversal_of_id IS NOT NULL AND amount_minor < 0)
            )');

        // allocated_minor имеет смысл только для положительных донатов.
        DB::statement('ALTER TABLE donations ADD CONSTRAINT donations_allocated_within_amount
            CHECK (
                allocated_minor >= 0
                AND (reversal_of_id IS NOT NULL OR allocated_minor <= amount_minor)
            )');

        DB::statement("ALTER TABLE donations ADD CONSTRAINT donations_fund_type_valid
            CHECK (fund_type IN ('general', 'zakat', 'sadaqah', 'waqf'))");

        // Запрет UPDATE денежных полей на уровне БД, а не только код-ревью.
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION forbid_donation_money_update() RETURNS trigger AS $$
            BEGIN
                IF NEW.amount_minor IS DISTINCT FROM OLD.amount_minor
                    OR NEW.currency IS DISTINCT FROM OLD.currency
                    OR NEW.fund_type IS DISTINCT FROM OLD.fund_type
                    OR NEW.reversal_of_id IS DISTINCT FROM OLD.reversal_of_id
                THEN
                    RAISE EXCEPTION 'donations is append-only: money fields cannot be updated, insert a reversal instead';
                END IF;
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;

            CREATE TRIGGER donations_forbid_money_update
                BEFORE UPDATE ON donations
                FOR EACH ROW EXECUTE FUNCTION forbid_donation_money_update();
        SQL);
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS donations_forbid_money_update ON donations');
        DB::unprepared('DROP FUNCTION IF EXISTS forbid_donation_money_update');
        Schema::dropIfExists('donations');
    }
};
