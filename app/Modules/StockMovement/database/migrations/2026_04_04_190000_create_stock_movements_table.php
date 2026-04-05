<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('tenant_id'); $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('warehouse_id'); $table->unsignedBigInteger('from_location_id')->nullable();
            $table->unsignedBigInteger('to_location_id')->nullable(); $table->string('movement_type');
            $table->decimal('quantity',15,4); $table->decimal('unit_cost',15,4)->default(0);
            $table->string('reference')->nullable(); $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable(); $table->timestamp('moved_at')->nullable();
            $table->timestamps(); $table->softDeletes(); $table->index(['tenant_id','product_id']); $table->index(['tenant_id','warehouse_id']);
        });
    }
    public function down(): void { Schema::dropIfExists('stock_movements'); }
};
