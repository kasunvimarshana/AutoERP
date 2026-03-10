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
            $table->uuid('id')->primary();
            $table->string('tenant_id')->index();
            $table->foreignUuid('category_id')->constrained('categories');
            $table->string('name');
            $table->string('code')->comment('Internal product code');
            $table->string('sku')->nullable()->comment('Stock Keeping Unit');
            $table->string('barcode')->nullable();
            $table->text('description')->nullable();
            $table->decimal('price', 12, 4)->default(0);
            $table->decimal('cost_price', 12, 4)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->string('unit')->nullable();
            $table->decimal('weight', 8, 3)->nullable();
            $table->json('dimensions')->nullable();
            $table->string('status')->default('active');
            $table->json('attributes')->nullable();
            $table->json('metadata')->nullable();
            $table->string('image_url')->nullable();
            $table->boolean('is_trackable')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'code']);
            $table->unique(['tenant_id', 'sku']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
