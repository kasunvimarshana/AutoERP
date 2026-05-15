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
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->json('metadata')->nullable();

            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->string('batch_number');
            $table->string('lot_number')->nullable();
            $table->date('manufacture_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->date('received_date')->nullable();
            $table->foreignId('supplier_id')->nullable()->constrained('parties')->nullOnDelete();
            $table->string('status')->default('active');
            $table->text('notes')->nullable();
            $table->decimal('sales_price', 20, 4)->nullable();
            $table->foreignId('lot_master_id')->nullable()->after('lot_number')->constrained('lot_masters')->nullOnDelete();

            $table->timestamps();

            $table->unique(
                ['tenant_id', 'organization_unit_id', 'product_id', 'variant_id', 'batch_number'],
                'batches_product_variant_batch_uk'
            );
            $table->index(['tenant_id', 'organization_unit_id', 'product_id', 'variant_id', 'batch_number'], 'batches_product_variant_batch_idx');
            $table->index(['tenant_id', 'organization_unit_id', 'product_id', 'variant_id', 'lot_number'], 'batches_product_variant_lot_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('batches');
    }
};
