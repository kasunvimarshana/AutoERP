<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_lots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->string('lot_code');
            $table->string('batch_code')->nullable();
            $table->date('manufactured_at')->nullable();
            $table->date('received_at')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('status')->default("active");
            $table->json('metadata')->nullable();
            $table->unique(['tenant_id', 'lot_code']);
            $table->index(['tenant_id', 'product_variant_id']);
            $table->index(['tenant_id', 'expiry_date']);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_lots');
    }
};
