<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotion_card_lifecycle_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', indexName: 'promotion_card_events_tenant_fk')->restrictOnDelete();
            $table->foreignId('card_id');
            $table->string('action', 20);
            $table->foreignId('old_vehicle_id')->nullable();
            $table->foreignId('new_vehicle_id')->nullable();
            $table->string('card_number_snapshot', 100);
            $table->string('old_status', 20)->nullable();
            $table->string('new_status', 20);
            $table->foreignId('actor_id')->nullable();
            $table->text('reason')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->unique(['id', 'tenant_id'], 'promotion_card_events_id_tenant_uk');
            $table->index(['tenant_id', 'card_id', 'occurred_at'], 'promotion_card_events_history_ix');
            $table->foreign(['card_id', 'tenant_id'], 'promotion_card_events_card_fk')->references(['id', 'tenant_id'])->on('promotion_cards')->restrictOnDelete();
            $table->foreign(['old_vehicle_id', 'tenant_id'], 'promotion_card_events_old_vehicle_fk')->references(['id', 'tenant_id'])->on('vehicles')->restrictOnDelete();
            $table->foreign(['new_vehicle_id', 'tenant_id'], 'promotion_card_events_new_vehicle_fk')->references(['id', 'tenant_id'])->on('vehicles')->restrictOnDelete();
            $table->foreign(['actor_id', 'tenant_id'], 'promotion_card_events_actor_fk')->references(['id', 'tenant_id'])->on('users')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotion_card_lifecycle_events');
    }
};
