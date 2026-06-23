<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_event_outbox', function (Blueprint $table): void {
            $table->id();
            $table->uuid('event_uuid')->unique('tenant_event_outbox_uuid_uk');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->restrictOnDelete();
            $table->string('event_type', 120);
            $table->json('payload');
            $table->enum('status', ['pending', 'processing', 'published'])->default('pending');
            $table->unsignedInteger('attempts')->default(0);
            $table->string('last_error', 500)->nullable();
            $table->timestamp('available_at');
            $table->uuid('claim_token')->nullable();
            $table->timestamp('claimed_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'available_at'], 'tenant_event_outbox_due_idx');
            $table->index(['status', 'claimed_at'], 'tenant_event_outbox_claim_idx');
            $table->index(['tenant_id', 'event_type'], 'tenant_event_outbox_tenant_type_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_event_outbox');
    }
};
