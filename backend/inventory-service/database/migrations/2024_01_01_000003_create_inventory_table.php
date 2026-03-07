<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void
    {
        Schema::create('inventory', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('product_id');
            $table->foreignId('warehouse_id')->constrained('warehouses')->onDelete('cascade');
            $table->integer('quantity')->default(0);
            $table->integer('reserved_quantity')->default(0);
            $table->integer('reorder_level')->default(10);
            $table->integer('max_quantity')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'product_id', 'warehouse_id']);
            $table->index(['tenant_id', 'product_id']);
        });
    }
    public function down(): void { Schema::dropIfExists('inventory'); }
};
