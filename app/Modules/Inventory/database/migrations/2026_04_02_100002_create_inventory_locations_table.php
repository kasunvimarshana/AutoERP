<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_locations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('warehouse_id');
            $table->unsignedBigInteger('zone_id')->nullable();
            $table->string('code', 100)->nullable();
            $table->string('name', 255);
            $table->string('type', 50);
            $table->string('aisle', 50)->nullable();
            $table->string('row', 50)->nullable();
            $table->string('level', 50)->nullable();
            $table->string('bin', 50)->nullable();
            $table->decimal('capacity', 15, 4)->nullable();
            $table->decimal('weight_limit', 15, 4)->nullable();
            $table->string('barcode', 255)->nullable();
            $table->string('qr_code', 255)->nullable();
            $table->boolean('is_pickable')->default(true);
            $table->boolean('is_storable')->default(true);
            $table->boolean('is_packing')->default(false);
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('tenant_id');
            $table->index('warehouse_id');
            $table->index('zone_id');
            $table->index('type');
            $table->index(['tenant_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_locations');
    }
};
