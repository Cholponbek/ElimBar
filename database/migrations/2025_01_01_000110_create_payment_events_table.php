<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Идемпотентность вебхуков. Обработчик обязан: INSERT ... ON CONFLICT DO
     * NOTHING на (provider, external_event_id), затем обрабатывать payload
     * только если вставка реально произошла (см. Domain\Payments\WebhookGuard).
     */
    public function up(): void
    {
        Schema::create('payment_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $table->string('provider');
            $table->string('external_event_id');
            $table->jsonb('payload');
            $table->timestamp('received_at');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'external_event_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_events');
    }
};
