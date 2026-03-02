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
            $table->string('sku', 100);
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->string('type', 50)->default('stockable');
            $table->string('uom', 50)->default('pcs');
            $table->string('costing_method', 50)->default('fifo');
            $table->decimal('cost_price', 20, 4)->default('0.0000');
            $table->decimal('sale_price', 20, 4)->default('0.0000');
            $table->string('barcode', 100)->nullable();
            $table->string('status', 20)->default('active');
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['tenant_id', 'sku']);
            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
