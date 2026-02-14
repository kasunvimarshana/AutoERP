<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('batch_lots', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->uuid('branch_id')->index();
            $table->uuid('product_variant_id')->index();
            $table->string('batch_number', 100)->index();
            $table->string('lot_number', 100)->nullable();
            $table->integer('quantity_received')->default(0);
            $table->integer('quantity_remaining')->default(0);
            $table->decimal('unit_cost', 12, 2)->default(0);
            $table->date('manufacture_date')->nullable();
            $table->date('expiry_date')->nullable()->index();
            $table->enum('status', ['active', 'expired', 'depleted'])->default('active')->index();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('branch_id')->references('id')->on('organizations')->onDelete('cascade');
            $table->foreign('product_variant_id')->references('id')->on('product_variants')->onDelete('cascade');
            
            $table->index(['tenant_id', 'branch_id', 'product_variant_id']);
            $table->index(['product_variant_id', 'status']);
            $table->index(['batch_number', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('batch_lots');
    }
};
