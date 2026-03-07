<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Transactional Outbox pattern — persists domain events alongside the business
 * data in the same DB transaction, guaranteeing at-least-once delivery to the
 * message broker even when the broker is temporarily unavailable.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outbox_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            $table->string('aggregate_type');          // e.g. 'Order', 'Inventory'
            $table->unsignedBigInteger('aggregate_id')->nullable();
            $table->string('event_type');              // e.g. 'order.created'
            $table->json('payload');                   // Full serialized event payload
            $table->enum('status', ['pending', 'published', 'failed'])->default('pending');
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);   // Used by the outbox processor query
            $table->index('event_type');
            $table->index(['aggregate_type', 'aggregate_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outbox_messages');
    }
};
