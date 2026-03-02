<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('picking_order_lines', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->foreignId('picking_order_id')->constrained('picking_orders')->cascadeOnDelete();
            $table->unsignedBigInteger('product_id');
            $table->foreignId('from_bin_id')->nullable()->constrained('bin_locations')->nullOnDelete();
            $table->decimal('quantity_requested', 20, 4);
            $table->decimal('quantity_picked', 20, 4)->default(0);
            $table->string('status')->default('pending'); // pending/picked/short
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('picking_order_lines');
    }
};
