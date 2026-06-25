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
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
            $table->foreignId('vehicle_service_job_id')->constrained('vehicle_service_jobs')->cascadeOnDelete();
            $table->foreignId('parent_line_id')->nullable()->constrained('vehicle_service_job_lines')->cascadeOnDelete();
            $table->unsignedInteger('line_number');
            $table->string('line_source_type', 30);
            $table->foreignId('item_id')->nullable()->constrained('items')->restrictOnDelete();
            $table->foreignId('item_variant_id')->nullable()->constrained('item_variants')->nullOnDelete();
            $table->foreignId('uom_id')->nullable()->constrained('unit_of_measures')->nullOnDelete();
            $table->text('description');
            $table->decimal('quantity', 20, 6);
            $table->decimal('unit_cost', 20, 6)->default('0.000000');
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
            $table->foreignId('inventory_movement_id')->nullable()->constrained('inventory_movements')->nullOnDelete();
            $table->string('status', 30)->default('pending');
            $table->timestamps();

            $table->unique(['vehicle_service_job_id', 'line_number'], 'vehicle_service_job_lines_job_number_uk');
            $table->index(['tenant_id', 'organization_unit_id'], 'vehicle_service_job_lines_tenant_org_idx');
            $table->index(['vehicle_service_job_id', 'line_source_type'], 'vehicle_service_job_lines_job_type_idx');
            $table->index('parent_line_id', 'vehicle_service_job_lines_parent_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_service_job_lines');
    }
};
