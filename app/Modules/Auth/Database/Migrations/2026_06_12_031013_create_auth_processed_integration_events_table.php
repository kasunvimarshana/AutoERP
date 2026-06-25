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
            $table->uuid('event_uuid');
            $table->string('consumer_name', 150);
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->restrictOnDelete();
            $table->timestamp('processed_at');

            $table->unique(['event_uuid', 'consumer_name'], 'auth_processed_events_event_consumer_uk');
            $table->index(['tenant_id', 'processed_at'], 'auth_processed_events_tenant_processed_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auth_processed_integration_events');
    }
};
