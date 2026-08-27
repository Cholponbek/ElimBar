<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Триггерные функции по умолчанию в Postgres — SECURITY INVOKER: они
     * выполняются с правами роли, которая сделала INSERT, а не владельца
     * функции. enforce_zakat_allocation() и apply_allocation() читают/
     * пишут cases (SELECT allows_zakat, UPDATE allocated_minor), а
     * apply_disbursement() пишет cases.disbursed_minor — но у app_public
     * на cases нет вообще никаких прав (осознанный REVOKE ALL, см.
     * setup_row_level_security). Донор со страницы кейса физически не
     * может создать Allocation: любая вставка в allocations падала бы с
     * "permission denied for table cases" ещё до проверки самого
     * инварианта — не баг доверия, а баг "фича вообще не работает".
     *
     * SECURITY DEFINER заставляет триггер выполняться с правами владельца
     * функции (роль миграций), независимо от того, кто вставляет строку
     * в allocations/disbursements. Это безопасно именно потому, что
     * логика функции фиксирована в коде миграции, а не собирается из
     * пользовательского ввода — donor не получает никаких новых прав за
     * пределами того, что делает сама функция (проверить и пересчитать
     * агрегаты). SET search_path = public — обязательная защита от
     * search_path hijacking для SECURITY DEFINER функций.
     */
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION enforce_zakat_allocation() RETURNS trigger
                LANGUAGE plpgsql
                SECURITY DEFINER
                SET search_path = public
            AS $$
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
            $$;

            CREATE OR REPLACE FUNCTION apply_allocation() RETURNS trigger
                LANGUAGE plpgsql
                SECURITY DEFINER
                SET search_path = public
            AS $$
            BEGIN
                UPDATE donations
                    SET allocated_minor = allocated_minor + NEW.amount_minor
                    WHERE id = NEW.donation_id;

                UPDATE cases
                    SET allocated_minor = allocated_minor + NEW.amount_minor
                    WHERE id = NEW.case_id;

                RETURN NEW;
            END;
            $$;

            CREATE OR REPLACE FUNCTION apply_disbursement() RETURNS trigger
                LANGUAGE plpgsql
                SECURITY DEFINER
                SET search_path = public
            AS $$
            BEGIN
                UPDATE cases
                    SET disbursed_minor = disbursed_minor + NEW.amount_minor
                    WHERE id = NEW.case_id;

                RETURN NEW;
            END;
            $$;
        SQL);
    }

    public function down(): void
    {
        DB::unprepared(<<<'SQL'
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

            CREATE OR REPLACE FUNCTION apply_disbursement() RETURNS trigger AS $$
            BEGIN
                UPDATE cases
                    SET disbursed_minor = disbursed_minor + NEW.amount_minor
                    WHERE id = NEW.case_id;

                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        SQL);
    }
};
