<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Тематический индикатор сбора на странице кейса (вместо обычного
     * кольца прогресса) — выбирается один раз при заведении кейса в
     * админке. 'default' — существующее кольцо, без изменений.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE cases ADD COLUMN progress_theme VARCHAR(30) NOT NULL DEFAULT 'default'");
        DB::statement("ALTER TABLE cases ADD CONSTRAINT cases_progress_theme_valid
            CHECK (progress_theme IN ('default', 'warmth_house', 'happy_coin'))");

        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE VIEW cases_public AS
            SELECT
                id, tenant_id, campaign_id, category, status,
                public_title, public_story, public_photo_path,
                currency, budget_minor, allocated_minor, disbursed_minor,
                allows_zakat, closed_at, created_at, updated_at,
                public_photo_paths, closure_report_description,
                end_date, closure_report_photo_paths, progress_theme
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
                public_photo_paths, closure_report_description,
                end_date, closure_report_photo_paths
            FROM cases
            WHERE status <> 'draft'
              AND tenant_id = current_setting('app.tenant_id', true)::bigint;
        SQL);

        DB::statement('GRANT SELECT ON cases_public TO app_public');

        DB::statement('ALTER TABLE cases DROP CONSTRAINT cases_progress_theme_valid');
        DB::statement('ALTER TABLE cases DROP COLUMN progress_theme');
    }
};
