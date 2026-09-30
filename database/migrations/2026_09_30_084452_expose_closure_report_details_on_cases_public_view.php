<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Полноценная публичная страница отчёта (/cases/{id}/report) требует
     * ещё end_date (дата окончания/бессрочный) и closure_report_photo_paths
     * (фото отчёта). financial_documents_paths туда намеренно не идёт —
     * финансовые/юридические документы остаются приватными.
     */
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE VIEW cases_public AS
            SELECT
                id, tenant_id, campaign_id, category, status,
                public_title, public_story, public_photo_path,
                currency, budget_minor, allocated_minor, disbursed_minor,
                allows_zakat, closed_at, created_at, updated_at,
                public_photo_paths, closure_report_description,
                end_date, closure_report_photo_paths
            FROM cases
            WHERE status <> 'draft'
              AND tenant_id = current_setting('app.tenant_id', true)::bigint;
        SQL);

        DB::statement('GRANT SELECT ON cases_public TO app_public');
    }

    public function down(): void
    {
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE VIEW cases_public AS
            SELECT
                id, tenant_id, campaign_id, category, status,
                public_title, public_story, public_photo_path,
                currency, budget_minor, allocated_minor, disbursed_minor,
                allows_zakat, closed_at, created_at, updated_at,
                public_photo_paths, closure_report_description
            FROM cases
            WHERE status <> 'draft'
              AND tenant_id = current_setting('app.tenant_id', true)::bigint;
        SQL);

        DB::statement('GRANT SELECT ON cases_public TO app_public');
    }
};
