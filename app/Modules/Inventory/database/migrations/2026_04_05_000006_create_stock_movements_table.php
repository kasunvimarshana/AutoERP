<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', static function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->foreignId('location_id')->constrained('locations')->cascadeOnDelete();
            $table->foreignId('batch_lot_id')->nullable()->constrained('batch_lots')->nullOnDelete();
            $table->foreignId('serial_number_id')->nullable()->constrained('serial_numbers')->nullOnDelete();
            $table->enum('movement_type', ['receipt', 'issue', 'transfer', 'adjustment', 'return_in', 'return_out', 'scrap']);
            $table->decimal('quantity', 20, 6);
            $table->decimal('unit_cost', 20, 6)->nullable();
            $table->string('reference', 255)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('moved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('moved_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'product_id']);
            $table->index(['tenant_id', 'movement_type']);
            $table->index(['reference']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
