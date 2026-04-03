<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_batches', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('variation_id')->nullable();
            $table->string('batch_number', 100);
            $table->string('lot_number', 100)->nullable();
            $table->date('manufacture_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->date('best_before_date')->nullable();
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->string('supplier_batch_ref', 255)->nullable();
            $table->decimal('initial_qty', 15, 4)->default(0);
            $table->decimal('remaining_qty', 15, 4)->default(0);
            $table->decimal('unit_cost', 15, 4)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->string('status', 50)->default('active');
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'batch_number', 'product_id']);
            $table->index('tenant_id');
            $table->index('product_id');
            $table->index('batch_number');
            $table->index('lot_number');
            $table->index('expiry_date');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_batches');
    }
};
