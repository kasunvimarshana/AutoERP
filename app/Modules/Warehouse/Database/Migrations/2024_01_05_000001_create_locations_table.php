<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('warehouse_id');
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->enum('type', ['zone', 'aisle', 'rack', 'shelf', 'bin', 'floor', 'other']);
            $table->unsignedTinyInteger('level')->default(0);
            $table->string('path', 1000)->nullable();
            $table->decimal('capacity', 18, 4)->nullable();
            $table->boolean('is_pickable')->default(true);
            $table->boolean('is_receivable')->default(true);
            $table->boolean('is_active')->default(true);
            $table->string('barcode', 100)->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->cascadeOnDelete();
            $table->foreign('parent_id')->references('id')->on('locations')->nullOnDelete();

            $table->index(['tenant_id', 'warehouse_id', 'parent_id']);
            $table->index('path');
            $table->index('barcode');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};