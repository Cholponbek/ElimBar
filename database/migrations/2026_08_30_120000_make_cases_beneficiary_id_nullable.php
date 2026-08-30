<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * До сих пор cases.beneficiary_id было обязательным — модель была
     * заточена под "один кейс = один конкретный человек с закрытым ФИО за
     * приватной стеной beneficiaries". Донор попросил категорию для
     * собственных проектов фонда (школьная форма сиротам, помощь
     * малоимущим семьям) — это сборы не про одного человека, придумывать
     * фиктивного "представителя" ради NOT NULL было бы враньём донору.
     * Ослабляем именно это ограничение, не заводя отдельную сущность:
     * такие кейсы (category = fund_project) стафф создаёт в FundCaseResource
     * напрямую, минуя requests/beneficiaries — see FundCaseResource.php.
     *
     * doctrine/dbal не установлен (Blueprint::change() им пользуется),
     * поэтому меняем колонку сырым SQL, как и остальные Postgres-специфичные
     * DDL в этом проекте.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE cases ALTER COLUMN beneficiary_id DROP NOT NULL');
    }

    public function down(): void
    {
        // best-effort: откат упадёт, если к этому моменту уже есть кейсы
        // категории fund_project без бенефициара — это ожидаемо для отката
        // структурного изменения на "грязных" данных.
        DB::statement('ALTER TABLE cases ALTER COLUMN beneficiary_id SET NOT NULL');
    }
};
