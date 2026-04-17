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
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->morphs('entity'); // product, batch, serial, etc.
            $table->foreignId('identifier_id')->nullable()->constrained('product_identifiers')->nullOnDelete();
            $table->enum('action_type', [
                'scan', 'receive', 'transfer', 'pick', 'pack', 'ship',
                'return', 'adjust', 'dispose', 'count'
            ]);
            $table->nullableMorphs('reference'); // GRN, shipment, transfer, etc.
            $table->foreignId('source_location_id')->nullable()->constrained('warehouse_locations')->nullOnDelete();
            $table->foreignId('destination_location_id')->nullable()->constrained('warehouse_locations')->nullOnDelete();
            $table->decimal('quantity', 15, 4)->nullable();
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('performed_at')->useCurrent();
            $table->string('device_id')->nullable();
            $table->json('metadata')->nullable();

            $table->index(['tenant_id', 'entity_type', 'entity_id'], 'idx_trace_logs_tenant_entity');
            $table->index(['tenant_id', 'performed_at'], 'idx_trace_logs_tenant_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trace_logs');
    }
};