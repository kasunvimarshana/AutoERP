<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_serial_numbers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('variation_id')->nullable();
            $table->unsignedBigInteger('batch_id')->nullable();
            $table->string('serial_number', 255);
            $table->unsignedBigInteger('location_id')->nullable();
            $table->string('status', 50)->default('available');
            $table->decimal('purchase_price', 15, 4)->nullable();
            $table->string('currency', 3)->default('USD');
            $table->timestamp('purchased_at')->nullable();
            $table->timestamp('sold_at')->nullable();
            $table->timestamp('returned_at')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('batch_id')->references('id')->on('inventory_batches')->nullOnDelete();
            $table->foreign('location_id')->references('id')->on('inventory_locations')->nullOnDelete();

            $table->unique(['tenant_id', 'product_id', 'serial_number']);
            $table->index('tenant_id');
            $table->index('product_id');
            $table->index('serial_number');
            $table->index('batch_id');
            $table->index('location_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_serial_numbers');
    }
};
