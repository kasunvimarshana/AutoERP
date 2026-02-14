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
        Schema::create('stock_ledger', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $table->foreignId('product_id')->nullable()->constrained('products')->onDelete('cascade');
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->onDelete('cascade');
            $table->foreignId('batch_id')->nullable()->constrained('batches')->onDelete('set null');
            $table->string('transaction_type'); // in, out, transfer, adjustment
            $table->string('reference_type')->nullable(); // purchase, sale, transfer, adjustment
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->decimal('quantity', 15, 4); // positive for in, negative for out
            $table->decimal('balance', 15, 4); // running balance after this transaction
            $table->decimal('unit_cost', 15, 4)->default(0);
            $table->decimal('total_cost', 15, 4)->default(0);
            $table->string('transaction_number')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->text('remarks')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('transaction_date');
            $table->timestamps();
            
            $table->index(['tenant_id', 'branch_id', 'product_id']);
            $table->index(['tenant_id', 'branch_id', 'product_variant_id']);
            $table->index(['tenant_id', 'transaction_date']);
            $table->index(['reference_type', 'reference_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_ledger');
    }
};
