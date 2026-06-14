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
            $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->restrictOnDelete();
            $table->foreignId('usage_log_id')->constrained('rental_usage_logs')->restrictOnDelete();
            $table->string('event_type', 30);
            $table->decimal('quantity', 20, 6);
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->unique(['usage_log_id', 'event_type'], 'rental_usage_events_log_type_uk');
            $table->index(['tenant_id', 'organization_unit_id'], 'rental_usage_events_tenant_org_idx');
            $table->index(['event_type', 'created_at'], 'rental_usage_events_type_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_usage_events');
    }
};
