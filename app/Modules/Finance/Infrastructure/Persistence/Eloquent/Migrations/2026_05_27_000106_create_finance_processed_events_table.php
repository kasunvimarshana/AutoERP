<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_processed_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')
                ->constrained('tenants', 'id')
                ->cascadeOnDelete()
                ->comment('Multi-tenant owner reference');
            $table->foreignId('organization_unit_id')
                ->nullable()
                ->constrained('organization_units', 'id')
                ->nullOnDelete()
                ->comment('Branch or department ownership');
            $table->json('metadata')->nullable()->comment('Extensible custom dynamic data');

            $table->string('event_type');
            $table->string('idempotency_key');
            $table->string('source_module')->nullable();
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->unsignedBigInteger('actor_user_id')->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('processed_at');

            $table->timestamps();

            $table->unique(
                ['tenant_id', 'event_type', 'idempotency_key'],
                'finance_processed_events_tenant_event_idempotency_uk'
            );
            $table->index(['tenant_id', 'processed_at'], 'finance_processed_events_processed_at_idx');
            $table->index(['tenant_id', 'journal_entry_id'], 'finance_processed_events_journal_entry_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_processed_events');
    }
};
