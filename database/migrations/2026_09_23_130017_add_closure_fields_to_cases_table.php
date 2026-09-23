<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Поля вкладки «Закрытие кейса» (Contour B, не публикуются в
     * cases_public — там явный список колонок, эта миграция его не
     * трогает). end_date = null означает «бессрочный кейс».
     */
    public function up(): void
    {
        Schema::table('cases', function (Blueprint $table) {
            $table->date('start_date')->nullable()->after('allows_zakat');
            $table->date('end_date')->nullable()->after('start_date');
            $table->jsonb('financial_documents_paths')->nullable()->after('end_date');
            $table->jsonb('closure_report_description')->nullable()->after('financial_documents_paths');
            $table->jsonb('closure_report_photo_paths')->nullable()->after('closure_report_description');
        });
    }

    public function down(): void
    {
        Schema::table('cases', function (Blueprint $table) {
            $table->dropColumn([
                'start_date',
                'end_date',
                'financial_documents_paths',
                'closure_report_description',
                'closure_report_photo_paths',
            ]);
        });
    }
};
