<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('batches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('product_id');
            $table->uuid('variant_id')->nullable();
            $table->string('batch_number')->unique();
            $table->string('lot_number')->nullable();
            $table->uuid('supplier_id')->nullable();
            $table->date('manufacture_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->decimal('received_quantity', 15, 4)->default(0);
            $table->decimal('available_quantity', 15, 4)->default(0);
            $table->decimal('unit_cost', 15, 4)->nullable();
            $table->text('notes')->nullable();
            $table->json('custom_attributes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->onDelete('cascade');

            $table->foreign('variant_id')
                ->references('id')
                ->on('product_variants')
                ->onDelete('cascade');

            // Indexes
            $table->index(['tenant_id', 'product_id']);
            $table->index(['tenant_id', 'expiry_date']);
            $table->index(['tenant_id', 'batch_number']);
            $table->index('available_quantity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('batches');
    }
};
