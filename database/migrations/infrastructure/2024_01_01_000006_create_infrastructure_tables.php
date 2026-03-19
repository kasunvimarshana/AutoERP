<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Outbox Pattern Events (Reliable Event Delivery)
        Schema::create('outbox_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('topic')->index(); // Kafka Topic or RabbitMQ Queue
            $table->string('event_type')->index(); // e.g., ProductCreatedEvent
            $table->json('payload');
            $table->enum('status', ['PENDING', 'PUBLISHED', 'FAILED'])->default('PENDING')->index();
            $table->text('error')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('created_at')->index();
        });

        // 2. Saga State (Distributed Transaction Management)
        Schema::create('saga_states', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('saga_type')->index(); // e.g., OrderPlacementSaga
            $table->string('saga_id')->index(); // Unique ID for the specific saga instance
            $table->json('payload'); // Initial input
            $table->json('results')->nullable(); // Results from each step
            $table->integer('current_step')->default(0);
            $table->enum('status', ['IN_PROGRESS', 'COMPLETED', 'FAILED', 'COMPENSATING', 'CANCELLED'])->default('IN_PROGRESS')->index();
            $table->text('error')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'saga_id']);
        });

        // 3. Metadata Definitions (Metadata-Driven Engine)
        Schema::create('metadata_definitions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('entity')->index(); // e.g., 'Product', 'Order'
            $table->string('type')->index(); // e.g., 'FORM', 'RULE', 'WORKFLOW'
            $table->string('name')->index(); // e.g., 'DEFAULT_FORM'
            $table->json('definition'); // The schema/metadata itself
            $table->integer('version')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['tenant_id', 'entity', 'type', 'name', 'version']);
        });

        // 4. Feature Flags (Dynamic Capabilities)
        Schema::create('feature_flags', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('key')->index();
            $table->boolean('is_enabled')->default(false);
            $table->json('rules')->nullable(); // Optional: Logic for when it's enabled (e.g., branch_id in [X, Y])
            $table->timestamps();

            $table->unique(['tenant_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feature_flags');
        Schema::dropIfExists('metadata_definitions');
        Schema::dropIfExists('saga_states');
        Schema::dropIfExists('outbox_events');
    }
};
