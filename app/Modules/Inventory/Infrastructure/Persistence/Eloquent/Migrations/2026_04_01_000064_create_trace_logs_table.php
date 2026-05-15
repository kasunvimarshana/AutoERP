<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trace_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->json('metadata')->nullable();

            $table->morphs('entity');
            $table->foreignId('identifier_id')->nullable()->constrained('product_identifiers')->nullOnDelete();
            $table->string('action_type');
            $table->nullableMorphs('reference');
            $table->foreignId('source_location_id')->nullable()->constrained('warehouse_locations')->nullOnDelete();
            $table->foreignId('destination_location_id')->nullable()->constrained('warehouse_locations')->nullOnDelete();
            $table->decimal('quantity', 20, 4)->nullable();
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('performed_at')->useCurrent();
            $table->string('device_id')->nullable();

            $table->timestamps();

            $table->index(['tenant_id', 'organization_unit_id', 'entity_type', 'entity_id'], 'trace_logs_entity_idx');
            $table->index(['tenant_id', 'organization_unit_id', 'performed_at'], 'trace_logs_performed_at_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trace_logs');
    }
};
