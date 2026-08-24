<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Append-only. Каждая вставка триггером увеличивает donations.allocated_minor
     * и cases.allocated_minor — оба под CHECK-констрейнтами своих таблиц
     * (donations_allocated_within_amount, cases_allocated_within_bounds
     * дополнительно ограничен бюджетом в приложении, allocated может расти
     * поверх budget — это осознанно: сборы часто идут до подтверждения
     * итогового бюджета; выплата же (disbursement) бюджет уже не может
     * превысить никогда).
     *
     * Триггер выполняется внутри той же транзакции, что и INSERT — если
     * UPDATE донат/кейса нарушает их CHECK, вся вставка аллокации
     * откатывается. Частичного состояния не бывает.
     */
    public function up(): void
    {
        Schema::create('allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $table->foreignId('donation_id')->constrained('donations')->restrictOnDelete();
            $table->foreignId('case_id')->constrained('cases')->restrictOnDelete();
            $table->bigInteger('amount_minor');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        DB::statement('ALTER TABLE allocations ADD CONSTRAINT allocations_amount_positive
            CHECK (amount_minor > 0)');

        DB::unprepared(<<<'SQL'
            -- Закят нельзя аллоцировать на кейс, который его не принимает.
            -- Это религиозное ограничение, а не UX-подсказка: живёт в БД.
            CREATE OR REPLACE FUNCTION enforce_zakat_allocation() RETURNS trigger AS $$
            DECLARE
                v_fund_type varchar;
                v_allows_zakat boolean;
            BEGIN
                SELECT fund_type INTO v_fund_type FROM donations WHERE id = NEW.donation_id;
                SELECT allows_zakat INTO v_allows_zakat FROM cases WHERE id = NEW.case_id;

                IF v_fund_type = 'zakat' AND v_allows_zakat IS DISTINCT FROM true THEN
                    RAISE EXCEPTION 'zakat donation % cannot be allocated to case % (allows_zakat = false)',
                        NEW.donation_id, NEW.case_id;
                END IF;

                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;

            CREATE TRIGGER allocations_enforce_zakat
                BEFORE INSERT ON allocations
                FOR EACH ROW EXECUTE FUNCTION enforce_zakat_allocation();

            -- Пересчёт агрегатов. UPDATE поднимает исключение через
            -- CHECK donations_allocated_within_amount / cases_allocated_within_bounds,
            -- если аллокация превышает донат.
            CREATE OR REPLACE FUNCTION apply_allocation() RETURNS trigger AS $$
            BEGIN
                UPDATE donations
                    SET allocated_minor = allocated_minor + NEW.amount_minor
                    WHERE id = NEW.donation_id;

                UPDATE cases
                    SET allocated_minor = allocated_minor + NEW.amount_minor
                    WHERE id = NEW.case_id;

                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;

            CREATE TRIGGER allocations_apply_after_insert
                AFTER INSERT ON allocations
                FOR EACH ROW EXECUTE FUNCTION apply_allocation();

            -- allocations тоже append-only.
            CREATE OR REPLACE FUNCTION forbid_allocation_mutation() RETURNS trigger AS $$
            BEGIN
                RAISE EXCEPTION 'allocations is append-only: no UPDATE or DELETE';
            END;
            $$ LANGUAGE plpgsql;

            CREATE TRIGGER allocations_forbid_update
                BEFORE UPDATE ON allocations
                FOR EACH ROW EXECUTE FUNCTION forbid_allocation_mutation();

            CREATE TRIGGER allocations_forbid_delete
                BEFORE DELETE ON allocations
                FOR EACH ROW EXECUTE FUNCTION forbid_allocation_mutation();
        SQL);
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS allocations_forbid_delete ON allocations');
        DB::unprepared('DROP TRIGGER IF EXISTS allocations_forbid_update ON allocations');
        DB::unprepared('DROP TRIGGER IF EXISTS allocations_apply_after_insert ON allocations');
        DB::unprepared('DROP TRIGGER IF EXISTS allocations_enforce_zakat ON allocations');
        DB::unprepared('DROP FUNCTION IF EXISTS forbid_allocation_mutation');
        DB::unprepared('DROP FUNCTION IF EXISTS apply_allocation');
        DB::unprepared('DROP FUNCTION IF EXISTS enforce_zakat_allocation');
        Schema::dropIfExists('allocations');
    }
};
