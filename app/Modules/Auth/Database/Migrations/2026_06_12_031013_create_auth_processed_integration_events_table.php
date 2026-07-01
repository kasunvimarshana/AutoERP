<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auth_processed_integration_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', indexName: 'auth_processed_integration_events_tenant_fk')->restrictOnDelete();
            $table->string('source_system', 100);
            $table->uuid('event_id');
            $table->string('event_type', 160);
            $table->dateTime('processed_at');
            $table->timestamps();

            $table->unique(['id', 'tenant_id'], 'auth_processed_events_id_tenant_uk');
            $table->unique(['tenant_id', 'source_system', 'event_id'], 'auth_event_idempotency_uk');
            $table->index('processed_at', 'auth_event_processed_ix');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auth_processed_integration_events');
    }
};
