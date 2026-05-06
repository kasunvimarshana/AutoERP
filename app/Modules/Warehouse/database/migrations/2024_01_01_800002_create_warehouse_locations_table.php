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

            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');

            $table->foreignId('warehouse_id')
                ->constrained('warehouses', 'id', 'warehouse_locations_warehouse_id_fk')
                ->cascadeOnDelete();
            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('warehouse_locations', 'id', 'warehouse_locations_parent_id_fk')
                ->nullOnDelete();
            $table->string('name');
            $table->string('code')->nullable();
            $table->string('path')->nullable();
            $table->unsignedInteger('depth')->default(0);
            $table->enum('type', ['zone', 'aisle', 'rack', 'shelf', 'bin', 'staging', 'dispatch'])->default('bin');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_pickable')->default(true);
            $table->boolean('is_receivable')->default(true);
            $table->decimal('capacity', 20, 6)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'org_unit_id', 'warehouse_id', 'code'], 'warehouse_locations_tenant_warehouse_code_uk');
            $table->index(['tenant_id', 'parent_id'], 'warehouse_locations_tenant_parent_idx');
            $table->index(['tenant_id', 'warehouse_id'], 'warehouse_locations_tenant_warehouse_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_locations');
    }
};
