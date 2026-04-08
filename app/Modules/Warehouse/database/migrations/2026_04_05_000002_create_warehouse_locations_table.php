<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouse_locations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->uuid('warehouse_id');
            $table->uuid('parent_id')->nullable();
            $table->string('code', 100);
            $table->string('name', 200);
            // zone, aisle, rack, shelf, bin
            $table->string('type', 30)->default('bin');
            $table->string('barcode', 100)->nullable()->index();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_pickable')->default(true);
            $table->boolean('is_receivable')->default(true);
            $table->decimal('max_weight', 10, 4)->nullable();
            $table->decimal('max_volume', 10, 4)->nullable();
            $table->integer('sort_order')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['warehouse_id', 'code']);
            $table->index(['warehouse_id']);
            $table->index(['parent_id']);

            $table->foreign('warehouse_id')->references('id')->on('warehouses')->cascadeOnDelete();
            $table->foreign('parent_id')->references('id')->on('warehouse_locations')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_locations');
    }
};
