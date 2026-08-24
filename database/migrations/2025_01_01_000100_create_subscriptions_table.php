<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Рекуррент — не подписка провайдера (её обычно нет), а своя: Horizon-джоба
     * читает next_charge_at, списывает по payment_token_ref (только ссылка на
     * токен у провайдера, не карта), при успехе создаёт donations-запись.
     */
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $table->foreignId('donor_id')->constrained('donors')->restrictOnDelete();
            $table->foreignId('case_id')->nullable()->constrained('cases')->nullOnDelete();
            $table->foreignId('campaign_id')->nullable()->constrained('campaigns')->nullOnDelete();

            $table->bigInteger('amount_minor');
            $table->char('currency', 3)->default('KGS');
            $table->string('frequency')->default('daily'); // daily|weekly|monthly

            $table->string('provider');
            $table->string('payment_token_ref');

            $table->string('status')->default('active'); // active|paused|cancelled
            $table->timestamp('next_charge_at');
            $table->unsignedTinyInteger('retry_stage')->default(0); // 0=on schedule, 1/2/3=lestnitsa 1/3/7 dney
            $table->timestamp('cancelled_at')->nullable();

            $table->timestamps();
        });

        DB::statement('ALTER TABLE subscriptions ADD CONSTRAINT subscriptions_amount_positive
            CHECK (amount_minor > 0)');

        DB::statement('ALTER TABLE donations ADD CONSTRAINT donations_subscription_id_foreign
            FOREIGN KEY (subscription_id) REFERENCES subscriptions (id) ON DELETE SET NULL');

        Schema::create('subscription_charges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $table->foreignId('subscription_id')->constrained('subscriptions')->restrictOnDelete();
            $table->foreignId('donation_id')->nullable()->constrained('donations')->nullOnDelete();
            $table->timestamp('attempted_at');
            $table->string('status'); // success|failed
            $table->unsignedTinyInteger('retry_stage')->default(0);
            $table->string('failure_reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_charges');
        DB::statement('ALTER TABLE donations DROP CONSTRAINT IF EXISTS donations_subscription_id_foreign');
        Schema::dropIfExists('subscriptions');
    }
};
