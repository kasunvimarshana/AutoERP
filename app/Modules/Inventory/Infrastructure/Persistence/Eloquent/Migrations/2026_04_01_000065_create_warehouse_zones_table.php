<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouse_zones', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->json('metadata')->nullable();

            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 50)->nullable();
            $table->string('zone_type')->default('storage');
            $table->decimal('max_weight', 20, 4)->nullable();
            $table->decimal('max_volume', 20, 4)->nullable();
            $table->integer('priority')->default(0);
            $table->string('temperature_range')->nullable();
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique(['tenant_id', 'organization_unit_id', 'warehouse_id', 'code'], 'warehouse_zones_code_uk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_zones');
    }
};
