<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Карусель на карточке кейса вместо одной фотографии. public_photo_path
     * оставлен как есть (история миграций не переписывается, тот же
     * прецедент что и с public_photo_id) — существующие кейсы с одной
     * фотографией переносятся в новый jsonb-массив прямо здесь, дальше
     * админка пишет только в public_photo_paths.
     *
     * CREATE OR REPLACE VIEW, не DROP+CREATE: колонка добавляется строго в
     * конец списка SELECT (иначе Postgres читает это как переименование
     * колонки на её позиции — "cannot change name of view column"),
     * существующие не трогаются — такое разрешено без сброса прав (в
     * отличие от переименования/удаления/вставки колонки в середину).
     */
    public function up(): void
    {
        Schema::table('cases', function (Blueprint $table) {
            $table->jsonb('public_photo_paths')->nullable()->after('public_photo_path');
        });

        DB::statement(<<<'SQL'
            UPDATE cases
            SET public_photo_paths = jsonb_build_array(public_photo_path)
            WHERE public_photo_path IS NOT NULL AND public_photo_path <> ''
        SQL);

        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE VIEW cases_public AS
            SELECT
                id, tenant_id, campaign_id, category, status,
                public_title, public_story, public_photo_path,
                currency, budget_minor, allocated_minor, disbursed_minor,
                allows_zakat, closed_at, created_at, updated_at,
                public_photo_paths
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
                allows_zakat, closed_at, created_at, updated_at
            FROM cases
            WHERE status <> 'draft'
              AND tenant_id = current_setting('app.tenant_id', true)::bigint;
        SQL);

        DB::statement('GRANT SELECT ON cases_public TO app_public');

        Schema::table('cases', function (Blueprint $table) {
            $table->dropColumn('public_photo_paths');
        });
    }
};
