<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Метаданные файла в приватном бакете MinIO. Сам файл не в БД — только
     * путь, хэш и кто загрузил. Публичного бакета для подтверждающих
     * документов не существует (ФИО и суммы в чеках).
     */
    public function up(): void
    {
        Schema::create('proofs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $table->string('disk')->default('proofs'); // config/filesystems.php: приватный MinIO-бакет
            $table->string('path');
            $table->char('sha256', 64);
            $table->string('original_name');
            $table->string('mime');
            $table->unsignedBigInteger('size_bytes');
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proofs');
    }
};
