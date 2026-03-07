<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventories', function (Blueprint $table): void {
            $table->id();

            // References product_id from the product-service (cross-service – not enforced by FK)
            $table->unsignedBigInteger('product_id');

            $table->unsignedInteger('quantity')->default(0);
            $table->unsignedInteger('reserved_quantity')->default(0);
            $table->string('warehouse_location', 255)->nullable();
            $table->unsignedInteger('reorder_level')->default(10);
            $table->unsignedInteger('reorder_quantity')->default(50);
            $table->decimal('unit_cost', 12, 4)->default(0.0000);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamp('last_counted_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('product_id');
            $table->index('status');
            $table->index('warehouse_location');
            $table->index(['product_id', 'status']);
            $table->index('quantity');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventories');
    }
};
