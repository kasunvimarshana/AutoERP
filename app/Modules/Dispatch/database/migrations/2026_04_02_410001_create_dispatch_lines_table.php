<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dispatch_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('dispatch_id')->index();
            $table->unsignedBigInteger('sales_order_line_id')->nullable();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('product_variant_id')->nullable();
            $table->text('description')->nullable();
            $table->decimal('quantity', 15, 4);
            $table->string('unit_of_measure', 50)->nullable();
            $table->string('status', 50)->default('pending');
            $table->unsignedBigInteger('warehouse_location_id')->nullable();
            $table->string('batch_number', 100)->nullable();
            $table->string('serial_number', 100)->nullable();
            $table->decimal('weight', 10, 3)->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['dispatch_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dispatch_lines');
    }
};
