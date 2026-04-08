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
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('product_code');
            $table->string('name');
            $table->string('product_type');
            $table->string('tracking_type')->default("none");
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->unique(['tenant_id', 'product_code']);
            $table->index(['tenant_id', 'category_id']);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
