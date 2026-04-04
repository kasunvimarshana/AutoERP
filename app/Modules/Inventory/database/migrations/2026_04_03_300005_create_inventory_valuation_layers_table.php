<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_valuation_layers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('warehouse_id');
            $table->string('valuation_method');
            $table->decimal('quantity', 15, 4)->default(0);
            $table->decimal('unit_cost', 15, 6)->default(0);
            $table->decimal('total_cost', 15, 4)->default(0);
            $table->unsignedBigInteger('batch_id')->nullable();
            $table->date('receipt_date')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'product_id', 'warehouse_id', 'valuation_method']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_valuation_layers');
    }
};
