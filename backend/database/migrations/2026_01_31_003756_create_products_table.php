<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->string('sku', 100)->unique();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->string('category', 100)->nullable();
            $table->string('unit_of_measure', 50);
            $table->boolean('is_variant_product')->default(false);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->enum('type', ['product', 'service'])->default('product');
            $table->decimal('tax_rate', 8, 4)->default(0);
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'type']);
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
