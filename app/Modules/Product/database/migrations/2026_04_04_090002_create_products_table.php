<?php

declare(strict_types=1);

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
            $table->string('name');
            $table->string('slug');
            $table->string('sku');
            $table->string('type');    // physical|service|digital|combo|variable
            $table->text('description')->nullable();
            $table->string('status')->default('active');
            $table->decimal('base_price', 15, 4)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('weight', 10, 4)->nullable();
            $table->string('unit')->default('each');
            $table->boolean('is_trackable')->default(true);
            $table->boolean('is_serialized')->default(false);
            $table->boolean('is_batch_tracked')->default(false);
            $table->decimal('min_stock_level', 15, 4)->nullable();
            $table->decimal('reorder_point', 15, 4)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id','sku']);
            $table->index('tenant_id');
        });
    }
    public function down(): void { Schema::dropIfExists('products'); }
};
