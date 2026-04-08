<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', static function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->foreign('category_id')->references('id')->on('categories')->nullOnDelete();
            $table->unsignedBigInteger('unit_of_measure_id')->nullable();
            $table->foreign('unit_of_measure_id')->references('id')->on('unit_of_measures')->nullOnDelete();
            $table->string('sku', 100);
            $table->string('barcode', 100)->nullable();
            $table->string('name', 255);
            $table->string('slug', 255);
            $table->string('short_description', 500)->nullable();
            $table->text('description')->nullable();
            $table->enum('type', ['physical', 'service', 'digital', 'combo', 'variable'])->default('physical');
            $table->enum('status', ['active', 'inactive', 'draft', 'discontinued'])->default('active');
            $table->boolean('is_purchasable')->default(true);
            $table->boolean('is_sellable')->default(true);
            $table->boolean('is_stockable')->default(true);
            $table->boolean('has_variants')->default(false);
            $table->boolean('has_serial_tracking')->default(false);
            $table->boolean('has_batch_tracking')->default(false);
            $table->boolean('has_expiry_tracking')->default(false);
            $table->decimal('cost_price', 20, 6)->nullable();
            $table->decimal('selling_price', 20, 6)->nullable();
            $table->decimal('min_selling_price', 20, 6)->nullable();
            $table->string('currency', 3)->default('USD');
            $table->string('tax_class', 100)->nullable();
            $table->decimal('weight', 10, 4)->nullable();
            $table->string('weight_unit', 10)->nullable();
            $table->json('dimensions')->nullable();
            $table->json('images')->nullable();
            $table->json('tags')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'sku']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
