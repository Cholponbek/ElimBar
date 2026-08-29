<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * public_intakes не входит в tenantScopedTables из
     * setup_row_level_security (создана позже) — тот же RLS-приём
     * применяется здесь отдельно, как и app_staff/app_public гранты.
     *
     * app_public получает только INSERT, без SELECT на содержимое: у
     * публичной формы нет сценария "прочитать чужую заявку обратно" —
     * доверять можно только "написать", не "прочитать" (иначе подбор id
     * открыл бы чужие ФИО/телефон/описание нужды). Подтверждение отправки
     * — просто flash-сообщение, не чтение записи.
     *
     * Единственное исключение — колонка id: Eloquent::create() всегда
     * делает INSERT ... RETURNING id, а Postgres требует SELECT-права на
     * возвращаемые колонки даже для INSERT (это не про "прочитать
     * заявку", а про то, что RETURNING технически тоже SELECT). Даём
     * SELECT только на id, не на всю строку.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE public_intakes ENABLE ROW LEVEL SECURITY');
        DB::statement('ALTER TABLE public_intakes FORCE ROW LEVEL SECURITY');

        DB::statement('DROP POLICY IF EXISTS tenant_isolation ON public_intakes');
        DB::statement(<<<'SQL'
            CREATE POLICY tenant_isolation ON public_intakes
                USING (tenant_id = current_setting('app.tenant_id', true)::bigint)
                WITH CHECK (tenant_id = current_setting('app.tenant_id', true)::bigint)
        SQL);

        DB::statement('GRANT SELECT, INSERT, UPDATE, DELETE ON public_intakes TO app_staff');
        DB::statement('GRANT USAGE, SELECT ON SEQUENCE public_intakes_id_seq TO app_staff');

        DB::statement('GRANT INSERT ON public_intakes TO app_public');
        DB::statement('GRANT SELECT (id) ON public_intakes TO app_public');
        DB::statement('GRANT USAGE ON SEQUENCE public_intakes_id_seq TO app_public');
    }

    public function down(): void
    {
        DB::statement('DROP POLICY IF EXISTS tenant_isolation ON public_intakes');
        DB::statement('ALTER TABLE public_intakes DISABLE ROW LEVEL SECURITY');
    }
};
