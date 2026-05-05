<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
$table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
$table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('product_id')->constrained(null, 'id', 'product_variants_product_id_fk')->cascadeOnDelete();
            $table->string('sku')->nullable();
            $table->string('name');
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->decimal('purchase_price', 20, 6)->nullable();
            $table->decimal('sales_price', 20, 6)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'product_id', 'sku'], 'product_variants_tenant_product_sku_uk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
