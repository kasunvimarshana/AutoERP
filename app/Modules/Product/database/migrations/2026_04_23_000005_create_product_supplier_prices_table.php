<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('product_supplier_prices', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('variant_id')->nullable();
            $table->unsignedBigInteger('supplier_id');
            $table->unsignedBigInteger('currency_id')->nullable();
            $table->unsignedBigInteger('uom_id');
            $table->decimal('min_order_quantity', 20, 6)->default(1);
            $table->decimal('unit_price', 20, 6);
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->unsignedInteger('lead_time_days')->default(0);
            $table->boolean('is_preferred')->default(false);
            $table->boolean('is_active')->default(true);
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('variant_id')->references('id')->on('product_variants')->onDelete('set null');
            $table->foreign('uom_id')->references('id')->on('units_of_measure')->onDelete('restrict');
            $table->index(['tenant_id', 'product_id', 'supplier_id'], 'psp_tenant_product_supplier_idx');
            $table->index(['tenant_id', 'supplier_id'], 'psp_tenant_supplier_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_supplier_prices');
    }
};
