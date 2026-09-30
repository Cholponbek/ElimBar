<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Пустое поле «Бюджет» в форме кейса = бюджет не ограничен, а не 0
     * (0 — это буквально нулевой бюджет, выплаты были бы вообще
     * запрещены). budget_minor NULL — специально разрешённое значение,
     * не «забыли заполнить»: cases_budget_non_negative уже пропускает
     * NULL (в Postgres CHECK проходит, если выражение NULL, а не
     * FALSE), а cases_disbursed_within_budget переопределяем явно —
     * без ограничения бюджета сумма выплат ничем сверху не лимитируется
     * (кроме здравого смысла модератора, который их создаёт).
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE cases ALTER COLUMN budget_minor DROP NOT NULL');
        DB::statement('ALTER TABLE cases ALTER COLUMN budget_minor DROP DEFAULT');

        DB::statement('ALTER TABLE cases DROP CONSTRAINT cases_disbursed_within_budget');
        DB::statement('ALTER TABLE cases ADD CONSTRAINT cases_disbursed_within_budget
            CHECK (disbursed_minor >= 0 AND (budget_minor IS NULL OR disbursed_minor <= budget_minor))');
    }

    public function down(): void
    {
        DB::statement('UPDATE cases SET budget_minor = 0 WHERE budget_minor IS NULL');

        DB::statement('ALTER TABLE cases DROP CONSTRAINT cases_disbursed_within_budget');
        DB::statement('ALTER TABLE cases ADD CONSTRAINT cases_disbursed_within_budget
            CHECK (disbursed_minor >= 0 AND disbursed_minor <= budget_minor)');

        DB::statement('ALTER TABLE cases ALTER COLUMN budget_minor SET DEFAULT 0');
        DB::statement('ALTER TABLE cases ALTER COLUMN budget_minor SET NOT NULL');
    }
};
