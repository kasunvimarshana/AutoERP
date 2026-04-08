<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_reservations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->uuid('product_id');
            $table->uuid('variant_id')->nullable();
            $table->uuid('warehouse_id');
            $table->uuid('location_id')->nullable();
            $table->decimal('quantity', 15, 4);
            $table->string('reference_type', 100)->nullable();
            $table->string('reference_id', 36)->nullable();
            // active, fulfilled, cancelled
            $table->string('status', 30)->default('active');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['product_id', 'warehouse_id', 'status']);
            $table->index(['reference_type', 'reference_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_reservations');
    }
};
