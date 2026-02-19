<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_prices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('product_id');
            $table->uuid('location_id')->nullable();
            $table->string('strategy');
            $table->decimal('price', 20, 10);
            $table->json('config')->nullable();
            $table->timestamp('valid_from')->nullable();
            $table->timestamp('valid_until')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('location_id')->references('id')->on('organizations')->onDelete('cascade');

            $table->index('tenant_id');
            $table->index('product_id');
            $table->index('location_id');
            $table->index(['product_id', 'location_id']);
            $table->index('strategy');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_prices');
    }
};
