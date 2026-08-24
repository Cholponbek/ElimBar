<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Донор — не User. Контур A (донор) и контур B (фонд) сознательно не
     * делят одну таблицу аутентификации: у донора нет пароля, вход по
     * телефону через OTP, у сотрудника фонда — email/пароль и Filament.
     */
    public function up(): void
    {
        Schema::create('donors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $table->string('phone', 20);
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('locale', 2)->default('ky');
            $table->timestamp('phone_verified_at')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'phone']);
        });

        Schema::create('otp_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $table->string('phone', 20);
            $table->string('code_hash');
            $table->timestamp('expires_at');
            $table->timestamp('consumed_at')->nullable();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamps();

            $table->index(['tenant_id', 'phone']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('otp_codes');
        Schema::dropIfExists('donors');
    }
};
