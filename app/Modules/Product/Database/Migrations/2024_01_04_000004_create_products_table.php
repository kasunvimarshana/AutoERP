<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('brand_id')->nullable();
            $table->string('sku', 100)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['physical', 'service', 'digital', 'combo', 'variable']);
            $table->unsignedBigInteger('base_uom_id');
            $table->unsignedBigInteger('purchase_uom_id')->nullable();
            $table->unsignedBigInteger('sales_uom_id')->nullable();
            $table->boolean('track_inventory')->default(true);
            $table->boolean('track_batch')->default(false);
            $table->boolean('track_serial')->default(false);
            $table->boolean('has_expiry')->default(false);
            $table->decimal('min_stock_level', 18, 4)->nullable();
            $table->decimal('reorder_point', 18, 4)->nullable();
            $table->enum('valuation_method', ['FIFO', 'LIFO', 'FEFO', 'WAC', 'SPECIFIC'])->default('FIFO');
            $table->unsignedBigInteger('inventory_account_id')->nullable();
            $table->unsignedBigInteger('cogs_account_id')->nullable();
            $table->unsignedBigInteger('income_account_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('category_id')->references('id')->on('categories')->nullOnDelete();
            $table->foreign('brand_id')->references('id')->on('brands')->nullOnDelete();
            $table->foreign('base_uom_id')->references('id')->on('units_of_measure')->cascadeOnDelete();
            $table->foreign('purchase_uom_id')->references('id')->on('units_of_measure')->nullOnDelete();
            $table->foreign('sales_uom_id')->references('id')->on('units_of_measure')->nullOnDelete();
            $table->foreign('inventory_account_id')->references('id')->on('chart_of_accounts')->nullOnDelete();
            $table->foreign('cogs_account_id')->references('id')->on('chart_of_accounts')->nullOnDelete();
            $table->foreign('income_account_id')->references('id')->on('chart_of_accounts')->nullOnDelete();

            $table->index(['tenant_id', 'sku']);
            $table->index(['tenant_id', 'type']);
            $table->index(['tenant_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};