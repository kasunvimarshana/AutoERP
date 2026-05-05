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
            $table->foreignId('tenant_id')->constrained(null, 'id', 'trace_logs_tenant_id_fk')->cascadeOnDelete();
            $table->morphs('entity'); // Product, Variant, Batch, Serial, Location, etc.
            $table->foreignId('identifier_id')->nullable()->constrained('product_identifiers', 'id', 'trace_logs_identifier_id_fk')->nullOnDelete();
            $table->enum('action_type', [
                'scan', 'receive', 'transfer', 'pick', 'pack', 'ship',
                'return', 'adjust', 'dispose', 'count',
            ]);
            $table->nullableMorphs('reference'); // GRN, shipment, transfer, etc.
            $table->foreignId('source_location_id')->nullable()->constrained('warehouse_locations', 'id', 'trace_logs_source_location_id_fk')->nullOnDelete();
            $table->foreignId('destination_location_id')->nullable()->constrained('warehouse_locations', 'id', 'trace_logs_destination_location_id_fk')->nullOnDelete();
            $table->decimal('quantity', 20, 6)->nullable();
            $table->foreignId('performed_by')->nullable()->constrained('users', 'id', 'trace_logs_performed_by_fk')->nullOnDelete();
            $table->timestamp('performed_at')->useCurrent();
            $table->string('device_id')->nullable();
            $table->json('metadata')->nullable(); // Contains GS1 AI data, scan context

            $table->index(['tenant_id', 'entity_type', 'entity_id'], 'trace_logs_tenant_entity_idx');
            $table->index(['tenant_id', 'performed_at'], 'trace_logs_tenant_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trace_logs');
    }
};
