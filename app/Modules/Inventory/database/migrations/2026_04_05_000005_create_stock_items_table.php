<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_items', static function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->foreignId('location_id')->constrained('locations')->cascadeOnDelete();
            $table->foreignId('batch_lot_id')->nullable()->constrained('batch_lots')->nullOnDelete();
            $table->decimal('quantity_on_hand', 20, 6)->default(0);
            $table->decimal('quantity_reserved', 20, 6)->default(0);
            $table->decimal('quantity_available', 20, 6)->default(0);
            $table->decimal('unit_cost', 20, 6)->default(0);
            $table->enum('status', ['available', 'reserved', 'in_transit', 'quarantine', 'scrapped'])->default('available');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['product_id', 'variant_id', 'location_id', 'batch_lot_id']);
            $table->index(['tenant_id', 'product_id']);
            $table->index(['location_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_items');
    }
};
