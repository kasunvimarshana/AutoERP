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
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('name');
            $table->string('sku');
            $table->string('type');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('uom_id');
            $table->unsignedBigInteger('buying_uom_id')->nullable();
            $table->unsignedBigInteger('selling_uom_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('has_serial_tracking')->default(false);
            $table->boolean('has_batch_tracking')->default(false);
            $table->boolean('has_expiry_tracking')->default(false);
            $table->string('barcode')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('uom_id')->references('id')->on('units_of_measure')->onDelete('restrict');
            $table->foreign('buying_uom_id')->references('id')->on('units_of_measure')->onDelete('restrict');
            $table->foreign('selling_uom_id')->references('id')->on('units_of_measure')->onDelete('restrict');
            $table->unique(['tenant_id', 'sku']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
