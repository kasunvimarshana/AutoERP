<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('batches', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete()->comment('Multi-tenant owner reference');
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete()->comment('Branch or department ownership');
            $table->json('metadata')->nullable()->comment('Extensible custom dynamic data');

            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('item_variants')->nullOnDelete();
            $table->string('batch_number');
            $table->string('lot_number')->nullable();
            $table->date('manufacture_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->date('received_date')->nullable();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->string('status')->default('active');
            $table->text('notes')->nullable();
            $table->decimal('unit_cost', 20, 4)->nullable();

            $table->timestamps();

            $table->unique(
                ['tenant_id', 'item_id', 'variant_id', 'batch_number'],
                'batches_item_variant_batch_uk'
            );
            $table->index(['tenant_id', 'item_id', 'variant_id', 'batch_number'], 'batches_item_variant_batch_idx');
            $table->index(['tenant_id', 'item_id', 'variant_id', 'lot_number'], 'batches_item_variant_lot_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('batches');
    }
};
