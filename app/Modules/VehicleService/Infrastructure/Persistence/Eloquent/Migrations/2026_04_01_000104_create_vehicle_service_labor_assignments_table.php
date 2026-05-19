<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_service_labor_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->json('metadata')->nullable();

            $table->string('reference')->nullable();
            // Link to the planned labour line
            $table->foreignId('labor_item_id')->constrained('vehicle_service_labor_items', 'id')->cascadeOnDelete();
            // Technician assigned to perform this work
            $table->foreignId('employee_id')->constrained('employees', 'id');
            // Actual hours worked on this specific labour item
            $table->decimal('hours_worked', 20, 4)->nullable();
            // Actual rate for this technician (may differ from the planned unit price)
            $table->decimal('hourly_rate', 20, 4)->nullable();
            // Incentive – stored both as configuration and as absolute amount
            $table->string('incentive_type')->nullable()->comment('percentage, fixed');
            $table->decimal('incentive_value', 20, 4)->default(0);
            $table->decimal('ncentive_amount', 20, 4)->default(0)->comment('Calculated ncentive amount');
            // Role of the technician (e.g., lead, assistant)
            $table->string('role')->nullable()->comment('lead, assistant, supervisor');
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->unique(['tenant_id', 'labor_item_id', 'employee_id'], 'vehicle_service_labor_assignments_labor_item_employee_uk');
            $table->index(['tenant_id', 'employee_id'], 'vehicle_service_labor_assignments_employee_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_service_labor_assignments');
    }
};
