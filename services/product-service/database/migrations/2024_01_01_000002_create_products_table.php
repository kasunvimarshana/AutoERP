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
            $table->string('tenant_id', 100)->index();
            $table->unsignedBigInteger('category_id')->nullable()->index();
            $table->string('sku', 100);
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->decimal('price', 14, 4)->default(0);
            $table->decimal('cost_price', 14, 4)->nullable();
            $table->string('currency', 3)->default('USD');
            $table->string('unit', 50)->nullable();
            $table->decimal('weight', 10, 3)->nullable();
            $table->json('dimensions')->nullable();
            $table->string('status', 50)->default('active')->index();
            $table->boolean('is_active')->default(true)->index();
            $table->json('metadata')->nullable();
            $table->json('tags')->nullable();
            $table->json('images')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['tenant_id', 'sku']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'is_active']);
            $table->index(['tenant_id', 'category_id']);
            $table->index(['tenant_id', 'created_at']);

            $table->foreign('category_id')
                ->references('id')
                ->on('categories')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
