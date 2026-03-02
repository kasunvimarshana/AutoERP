<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_reservations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('product_id');
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->decimal('quantity_reserved', 20, 4);
            $table->string('reference_type');
            $table->unsignedBigInteger('reference_id');
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_fulfilled')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_reservations');
    }
};
