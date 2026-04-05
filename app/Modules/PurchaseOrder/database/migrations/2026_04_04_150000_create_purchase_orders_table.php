<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('tenant_id'); $table->unsignedBigInteger('supplier_id');
            $table->unsignedBigInteger('warehouse_id')->nullable(); $table->string('po_number');
            $table->string('status')->default('draft'); $table->decimal('total_amount',15,4)->default(0);
            $table->string('currency',3)->default('USD'); $table->date('expected_date')->nullable();
            $table->text('notes')->nullable(); $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps(); $table->softDeletes(); $table->unique(['tenant_id','po_number']); $table->index('tenant_id');
        });
    }
    public function down(): void { Schema::dropIfExists('purchase_orders'); }
};
