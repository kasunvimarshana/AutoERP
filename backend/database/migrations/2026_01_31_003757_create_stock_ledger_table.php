<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_ledger', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->uuid('branch_id')->index();
            $table->uuid('inventory_id')->index();
            $table->uuid('product_variant_id')->index();
            $table->enum('transaction_type', ['in', 'out', 'adjustment', 'transfer'])->index();
            $table->integer('quantity');
            $table->decimal('unit_cost', 12, 2)->default(0);
            $table->string('reference_type', 50)->nullable();
            $table->string('reference_id', 255)->nullable();
            $table->string('batch_number', 100)->nullable()->index();
            $table->string('lot_number', 100)->nullable();
            $table->date('expiry_date')->nullable()->index();
            $table->text('notes')->nullable();
            $table->uuid('created_by')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('branch_id')->references('id')->on('organizations')->onDelete('cascade');
            $table->foreign('inventory_id')->references('id')->on('inventory')->onDelete('cascade');
            $table->foreign('product_variant_id')->references('id')->on('product_variants')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            
            $table->index(['tenant_id', 'branch_id', 'created_at']);
            $table->index(['product_variant_id', 'transaction_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_ledger');
    }
};
