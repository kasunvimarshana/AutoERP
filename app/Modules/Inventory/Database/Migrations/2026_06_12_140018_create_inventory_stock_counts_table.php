<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_stock_counts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', indexName: 'inventory_stock_counts_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->string('count_number', 80);
            $table->date('count_date');
            $table->string('count_type', 30)->default('stock_count');
            $table->foreignId('warehouse_id');
            $table->foreignId('warehouse_location_id')->nullable();
            $table->string('status', 30)->default('draft');
            $table->foreignId('inventory_adjustment_id')->nullable();
            $table->text('reason')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('posted_by')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'count_number'], 'inventory_stock_counts_tenant_number_uk');
            $table->index(['tenant_id', 'organization_unit_id'], 'inventory_stock_counts_scope_ix');
            $table->index(['warehouse_id', 'warehouse_location_id'], 'inventory_stock_counts_wh_ix');
            $table->index('status', 'inventory_stock_counts_status_ix');

            $table->unique(['id', 'tenant_id'], 'inventory_stock_counts_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'inventory_stock_counts_organization_unit_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['warehouse_id', 'tenant_id'], 'inventory_stock_counts_warehouse_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('warehouses')
                ->restrictOnDelete();
            $table->foreign(['warehouse_location_id', 'tenant_id'], 'inventory_stock_counts_warehouse_location_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('warehouse_locations')
                ->restrictOnDelete();
            $table->foreign(['inventory_adjustment_id', 'tenant_id'], 'inventory_stock_counts_inventory_adjustment_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('inventory_adjustments')
                ->restrictOnDelete();

            $table->foreign(['created_by', 'tenant_id'], 'inventory_stock_counts_created_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
            $table->foreign(['approved_by', 'tenant_id'], 'inventory_stock_counts_approved_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
            $table->foreign(['posted_by', 'tenant_id'], 'inventory_stock_counts_posted_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_stock_counts');
    }
};
