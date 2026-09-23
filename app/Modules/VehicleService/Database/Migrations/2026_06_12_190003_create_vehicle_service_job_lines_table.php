<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_service_job_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', indexName: 'vehicle_service_job_lines_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->foreignId('vehicle_service_job_id');
            $table->foreignId('parent_line_id')->nullable();
            $table->unsignedInteger('line_number');
            $table->string('line_source_type', 30);
            $table->foreignId('item_id')->nullable();
            $table->foreignId('item_variant_id')->nullable();
            $table->foreignId('batch_id')->nullable();
            $table->foreignId('batch_price_revision_id')->nullable();
            $table->foreignId('uom_id')->nullable();
            $table->text('description');
            $table->decimal('quantity', 20, 6);
            $table->decimal('unit_cost', 20, 6)->default('0.000000');
            $table->boolean('uses_job_supervisor')->default(false);
            $table->decimal('unit_price', 20, 6)->default('0.000000');
            $table->string('discount_calculation_type', 20)->nullable();
            $table->decimal('discount_rate', 20, 6)->default('0.000000');
            $table->decimal('discount_amount', 20, 6)->default('0.000000');
            $table->string('tax_calculation_type', 20)->nullable();
            $table->decimal('tax_rate', 20, 6)->default('0.000000');
            $table->decimal('tax_amount', 20, 6)->default('0.000000');
            $table->string('charge_calculation_type', 20)->nullable();
            $table->decimal('charge_rate', 20, 6)->default('0.000000');
            $table->decimal('charge_amount', 20, 6)->default('0.000000');
            $table->decimal('line_total', 20, 6)->default('0.000000');
            $table->boolean('is_inventory_tracked')->default(false);
            $table->boolean('is_customer_supplied')->default(false);
            $table->boolean('is_external')->default(false);
            $table->boolean('is_billable')->default(true);
            $table->boolean('is_employee_assignable')->default(false);
            $table->foreignId('inventory_movement_id')->nullable();
            $table->string('status', 30)->default('pending');
            $table->timestamps();

            $table->unique(['vehicle_service_job_id', 'line_number'], 'vehicle_service_job_lines_job_number_uk');
            $table->index(['tenant_id', 'organization_unit_id'], 'vehicle_service_job_lines_tenant_org_ix');
            $table->index(['vehicle_service_job_id', 'line_source_type'], 'vehicle_service_job_lines_job_type_ix');
            $table->index('parent_line_id', 'vehicle_service_job_lines_parent_ix');
            $table->index('batch_id', 'vehicle_service_job_lines_batch_ix');
            $table->index('batch_price_revision_id', 'vehicle_service_job_lines_batch_price_ix');

            $table->unique(['id', 'tenant_id'], 'vehicle_service_job_lines_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'vehicle_service_job_lines_organization_unit_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['vehicle_service_job_id', 'tenant_id'], 'vehicle_service_job_lines_vehicle_service_job_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('vehicle_service_jobs')
                ->cascadeOnDelete();
            $table->foreign(['parent_line_id', 'tenant_id'], 'vehicle_service_job_lines_parent_line_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('vehicle_service_job_lines')
                ->cascadeOnDelete();
            $table->foreign(['item_id', 'tenant_id'], 'vehicle_service_job_lines_item_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('items')
                ->restrictOnDelete();
            $table->foreign(['item_variant_id', 'tenant_id'], 'vehicle_service_job_lines_item_variant_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('item_variants')
                ->restrictOnDelete();
            $table->foreign(['batch_id', 'tenant_id'], 'vehicle_service_job_lines_batch_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('inventory_batches')
                ->restrictOnDelete();
            $table->foreign(['batch_price_revision_id', 'tenant_id'], 'vehicle_service_job_lines_batch_price_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('inventory_batch_price_revisions')
                ->restrictOnDelete();
            $table->foreign(['uom_id', 'tenant_id'], 'vehicle_service_job_lines_uom_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('unit_of_measures')
                ->restrictOnDelete();
            $table->foreign(['inventory_movement_id', 'tenant_id'], 'vehicle_service_job_lines_inventory_movement_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('inventory_movements')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_service_job_lines');
    }
};
