<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('dispatches', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('tenant_id'); $table->unsignedBigInteger('sales_order_id');
            $table->unsignedBigInteger('warehouse_id'); $table->string('dispatch_number');
            $table->string('status')->default('pending'); $table->string('carrier')->nullable();
            $table->string('tracking_number')->nullable(); $table->decimal('shipping_cost',15,4)->nullable();
            $table->timestamp('shipped_at')->nullable(); $table->timestamp('delivered_at')->nullable();
            $table->timestamps(); $table->softDeletes(); $table->unique(['tenant_id','dispatch_number']); $table->index('tenant_id');
        });
    }
    public function down(): void { Schema::dropIfExists('dispatches'); }
};
