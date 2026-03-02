<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('uom_conversions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('from_uom_id');
            $table->unsignedBigInteger('to_uom_id');
            // Stored as decimal string for BCMath — NOT float. Minimum 8 decimal places precision.
            $table->decimal('factor', 20, 8);
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('from_uom_id')->references('id')->on('units_of_measure')->onDelete('restrict');
            $table->foreign('to_uom_id')->references('id')->on('units_of_measure')->onDelete('restrict');
            $table->unique(['tenant_id', 'product_id', 'from_uom_id', 'to_uom_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uom_conversions');
    }
};
