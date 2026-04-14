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
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->enum('type', ['zone', 'aisle', 'rack', 'shelf', 'bin', 'floor', 'other']);
            $table->unsignedTinyInteger('level');
            $table->string('path', 1000);
            $table->decimal('capacity', 18, 4)->nullable();
            $table->boolean('is_pickable')->default(true);
            $table->boolean('is_receivable')->default(true);
            $table->boolean('is_active')->default(true);
            $table->string('barcode', 100)->nullable();
            $table->timestamps();

            $table->foreign('parent_id')->references('id')->on('locations')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};