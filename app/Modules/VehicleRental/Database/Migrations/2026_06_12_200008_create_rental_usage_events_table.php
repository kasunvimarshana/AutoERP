<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_usage_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
            $table->foreignId('usage_log_id')->constrained('rental_usage_logs')->cascadeOnDelete();
            $table->foreignId('agreement_id')->constrained('rental_agreements')->cascadeOnDelete();
            $table->string('event_type', 30);
            $table->decimal('quantity', 20, 6);
            $table->decimal('rate_snapshot', 20, 6);
            $table->decimal('amount', 20, 6);
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'organization_unit_id'], 'rental_usage_events_tenant_org_idx');
            $table->index(['agreement_id', 'event_type'], 'rental_usage_events_agreement_type_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_usage_events');
    }
};
