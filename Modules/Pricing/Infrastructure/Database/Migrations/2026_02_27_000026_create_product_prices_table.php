<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_prices', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('price_list_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('uom_id')->nullable();
            $table->decimal('buying_price', 20, 4);
            $table->decimal('selling_price', 20, 4);
            $table->decimal('min_quantity', 20, 4)->default('1.0000');
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('price_list_id')->references('id')->on('price_lists')->onDelete('cascade');
            $table->unique(['tenant_id', 'price_list_id', 'product_id', 'uom_id', 'valid_from']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_prices');
    }
};
