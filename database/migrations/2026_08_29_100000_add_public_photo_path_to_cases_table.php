<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * public_photo_id (FK -> proofs) не годится для обложки кейса:
     * app_public не имеет вообще никаких прав на proofs (staffOnlyTables
     * в setup_row_level_security) — публичный контроллер физически не
     * смог бы прочитать disk/path, чтобы построить ссылку на фото.
     * proofs — приватные документы о расходах с подписанными
     * короткоживущими ссылками, обложка кейса — наоборот, должна быть
     * обычной постоянной публичной картинкой. Отдельное поле с путём на
     * диске 'public', без обращения к proofs вообще. public_photo_id
     * оставлен как есть (история миграций не переписывается), просто не
     * используется.
     */
    public function up(): void
    {
        Schema::table('cases', function (Blueprint $table) {
            $table->string('public_photo_path')->nullable()->after('public_photo_id');
        });

        // CREATE OR REPLACE VIEW не даёт переименовать колонку на том же
        // месте ("cannot change name of view column") — только DROP+CREATE,
        // с повторным GRANT (DROP VIEW сбрасывает права).
        DB::unprepared(<<<'SQL'
            DROP VIEW IF EXISTS cases_public;

            CREATE VIEW cases_public AS
            SELECT
                id, tenant_id, campaign_id, category, status,
                public_title, public_story, public_photo_path,
                currency, budget_minor, allocated_minor, disbursed_minor,
                allows_zakat, closed_at, created_at, updated_at
            FROM cases
            WHERE status <> 'draft'
              AND tenant_id = current_setting('app.tenant_id', true)::bigint;
        SQL);

        DB::statement('GRANT SELECT ON cases_public TO app_public');
    }

    public function down(): void
    {
        DB::unprepared(<<<'SQL'
            DROP VIEW IF EXISTS cases_public;

            CREATE VIEW cases_public AS
            SELECT
                id, tenant_id, campaign_id, category, status,
                public_title, public_story, public_photo_id,
                currency, budget_minor, allocated_minor, disbursed_minor,
                allows_zakat, closed_at, created_at, updated_at
            FROM cases
            WHERE status <> 'draft'
              AND tenant_id = current_setting('app.tenant_id', true)::bigint;
        SQL);

        DB::statement('GRANT SELECT ON cases_public TO app_public');

        Schema::table('cases', function (Blueprint $table) {
            $table->dropColumn('public_photo_path');
        });
    }
};
