<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Append-only. proof_id NOT NULL — выплата без документа физически
     * невозможна, это FK-констрейнт, а не проверка в форме Filament.
     */
    public function up(): void
    {
        Schema::create('disbursements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $table->foreignId('case_id')->constrained('cases')->restrictOnDelete();
            $table->foreignId('proof_id')->constrained('proofs')->restrictOnDelete();
            $table->bigInteger('amount_minor');
            $table->char('currency', 3)->default('KGS');
            $table->text('recipient_note')->nullable();
            $table->foreignId('disbursed_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('disbursed_at');
            $table->foreignId('reversal_of_id')->nullable()->constrained('disbursements')->restrictOnDelete();
            $table->timestamps();
        });

        DB::statement('ALTER TABLE disbursements ADD CONSTRAINT disbursements_amount_sign
            CHECK (
                (reversal_of_id IS NULL AND amount_minor > 0)
                OR (reversal_of_id IS NOT NULL AND amount_minor < 0)
            )');

        DB::unprepared(<<<'SQL'
            -- disbursed_minor пересчитывается здесь; CHECK cases_disbursed_within_budget
            -- откатывает вставку, если выплата пробивает бюджет кейса.
            CREATE OR REPLACE FUNCTION apply_disbursement() RETURNS trigger AS $$
            BEGIN
                UPDATE cases
                    SET disbursed_minor = disbursed_minor + NEW.amount_minor
                    WHERE id = NEW.case_id;

                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;

            CREATE TRIGGER disbursements_apply_after_insert
                AFTER INSERT ON disbursements
                FOR EACH ROW EXECUTE FUNCTION apply_disbursement();

            CREATE OR REPLACE FUNCTION forbid_disbursement_mutation() RETURNS trigger AS $$
            BEGIN
                RAISE EXCEPTION 'disbursements is append-only: no UPDATE or DELETE, insert a reversal instead';
            END;
            $$ LANGUAGE plpgsql;

            CREATE TRIGGER disbursements_forbid_update
                BEFORE UPDATE ON disbursements
                FOR EACH ROW EXECUTE FUNCTION forbid_disbursement_mutation();

            CREATE TRIGGER disbursements_forbid_delete
                BEFORE DELETE ON disbursements
                FOR EACH ROW EXECUTE FUNCTION forbid_disbursement_mutation();
        SQL);
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS disbursements_forbid_delete ON disbursements');
        DB::unprepared('DROP TRIGGER IF EXISTS disbursements_forbid_update ON disbursements');
        DB::unprepared('DROP TRIGGER IF EXISTS disbursements_apply_after_insert ON disbursements');
        DB::unprepared('DROP FUNCTION IF EXISTS forbid_disbursement_mutation');
        DB::unprepared('DROP FUNCTION IF EXISTS apply_disbursement');
        Schema::dropIfExists('disbursements');
    }
};
