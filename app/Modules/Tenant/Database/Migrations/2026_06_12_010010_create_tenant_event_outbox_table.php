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
            $table->foreignId('tenant_id')->constrained('tenants', 'id', indexName: 'tenant_event_outbox_tenant_fk')->restrictOnDelete();
            $table->string('event_type', 120);
            $table->json('payload');
            $table->string('status', 40)->default('pending');
            $table->unsignedInteger('attempts')->default(0);
            $table->string('last_error_code', 100)->nullable();
            $table->string('last_error_message', 255)->nullable();
            $table->dateTime('available_at');
            $table->uuid('claim_token')->nullable();
            $table->timestamp('claimed_at')->nullable();
            $table->timestamp('claim_lease_expires_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('dead_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'available_at'], 'tenant_event_outbox_due_ix');
            $table->index(['status', 'claimed_at'], 'tenant_event_outbox_claim_ix');
            $table->index(['tenant_id', 'event_type'], 'tenant_event_outbox_tenant_type_ix');
            $table->unique(['id', 'tenant_id'], 'tenant_event_outbox_id_tenant_uk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_event_outbox');
    }
};
