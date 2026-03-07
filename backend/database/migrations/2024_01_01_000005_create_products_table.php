<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('sku', 100)->index();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category', 100)->index();
            $table->decimal('price', 12, 4)->default(0);
            $table->decimal('cost', 12, 4)->default(0);
            $table->unsignedInteger('stock_quantity')->default(0);
            $table->unsignedInteger('reserved_quantity')->default(0);
            $table->unsignedInteger('reorder_point')->default(0);
            $table->unsignedInteger('reorder_quantity')->default(0);
            $table->string('unit', 50)->nullable();
            $table->decimal('weight', 10, 3)->nullable();
            $table->json('dimensions')->nullable();
            $table->json('attributes')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_trackable')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'sku']);
            $table->index(['tenant_id', 'category']);
            $table->index(['tenant_id', 'is_active']);
            $table->index(['tenant_id', 'stock_quantity', 'reorder_point']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
