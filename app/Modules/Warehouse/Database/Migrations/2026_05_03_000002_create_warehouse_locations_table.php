<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouse_locations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete()->comment('Multi-tenant owner reference');
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete()->comment('Branch or department ownership');
            $table->json('metadata')->nullable()->comment('Extensible custom dynamic data');

            $table->foreignId('warehouse_id')->constrained('warehouses', 'id', 'warehouse_locations_warehouse_id_fk')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('warehouse_locations', 'id', 'warehouse_locations_parent_id_fk')->nullOnDelete();
            $table->string('name');
            $table->string('code')->nullable();
            $table->string('path')->nullable();
            $table->unsignedInteger('depth')->default(0);
            $table->string('type')->default('bin')->comment('zone, aisle, rack, shelf, bin, staging, dispatch');               // zone, aisle, rack, shelf, bin, staging, dispatch
            $table->boolean('is_active')->default(true);
            $table->boolean('is_pickable')->default(true);
            $table->boolean('is_receivable')->default(true);
            $table->decimal('capacity', 20, 4)->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'warehouse_id', 'name'], 'warehouse_locations_warehouse_name_uk');
            $table->index(['tenant_id', 'parent_id'], 'warehouse_locations_parent_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_locations');
    }
};
