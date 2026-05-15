<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('uom_conversions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->json('metadata')->nullable();

            $table->foreignId('from_uom_id')->constrained('units_of_measure');
            $table->foreignId('to_uom_id')->constrained('units_of_measure');
            $table->decimal('factor', 20, 10);
            $table->foreignId('product_id')->nullable()->constrained('products')->cascadeOnDelete();
            $table->boolean('is_bidirectional')->default(true);
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->unique(
                ['tenant_id', 'organization_unit_id', 'product_id', 'from_uom_id', 'to_uom_id'],
                'uom_conversions_product_from_to_uk'
            );
            $table->index(['tenant_id', 'organization_unit_id', 'product_id', 'is_active'], 'uom_conversions_product_active_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uom_conversions');
    }
};
