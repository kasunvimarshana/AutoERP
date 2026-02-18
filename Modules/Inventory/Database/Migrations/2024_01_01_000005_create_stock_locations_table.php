<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_locations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('warehouse_id');
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->enum('location_type', ['zone', 'aisle', 'rack', 'shelf', 'bin'])->default('bin');
            $table->string('aisle', 20)->nullable();
            $table->string('rack', 20)->nullable();
            $table->string('shelf', 20)->nullable();
            $table->string('bin', 20)->nullable();
            $table->decimal('capacity', 15, 2)->nullable();
            $table->string('capacity_unit', 20)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('tenant_id');
            $table->index('warehouse_id');
            $table->index('parent_id');
            $table->index('code');
            $table->index(['tenant_id', 'warehouse_id']);
            $table->index(['tenant_id', 'is_active']);
            $table->foreign('warehouse_id')->references('id')->on('warehouses');
            $table->foreign('parent_id')->references('id')->on('stock_locations')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_locations');
    }
};
