<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('serials', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->json('metadata')->nullable();

            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->string('serial_number');
            $table->foreignId('batch_id')->nullable()->constrained('batches')->nullOnDelete();
            $table->string('status')->default('available');
            $table->foreignId('current_location_id')->nullable()->constrained('warehouse_locations')->nullOnDelete();
            $table->nullableMorphs('current_owner');
            $table->date('warranty_expiry')->nullable();
            $table->text('notes')->nullable();
            $table->date('manufacture_date')->nullable();
            $table->decimal('sales_price', 20, 4)->nullable();

            $table->timestamps();

            $table->unique(['tenant_id', 'organization_unit_id', 'serial_number'], 'serials_number_uk');
            $table->index(['tenant_id', 'organization_unit_id', 'product_id'], 'serials_product_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('serials');
    }
};
