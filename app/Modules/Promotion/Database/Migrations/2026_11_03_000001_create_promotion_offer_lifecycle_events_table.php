<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotion_offer_lifecycle_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', indexName: 'promotion_offer_events_tenant_fk')->restrictOnDelete();
            $table->unsignedBigInteger('offer_id');
            $table->string('offer_code', 80);
            $table->string('offer_name');
            $table->string('action', 20);
            $table->string('old_status', 20)->nullable();
            $table->string('new_status', 20)->nullable();
            $table->foreignId('actor_id');
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['tenant_id', 'offer_id', 'occurred_at'], 'promotion_offer_events_history_ix');
            $table->unique(['id', 'tenant_id'], 'promotion_offer_events_id_tenant_uk');
            $table->foreign(['actor_id', 'tenant_id'], 'promotion_offer_events_actor_fk')
                ->references(['id', 'tenant_id'])->on('users')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotion_offer_lifecycle_events');
    }
};
