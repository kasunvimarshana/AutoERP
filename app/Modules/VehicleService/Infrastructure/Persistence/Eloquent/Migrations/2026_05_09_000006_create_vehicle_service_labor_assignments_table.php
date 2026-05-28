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
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete()->comment('Multi-tenant owner reference');
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete()->comment('Branch or department ownership');
            $table->json('metadata')->nullable()->comment('Extensible custom dynamic data');

            $table->string('reference')->nullable();
            $table->foreignId('job_card_id')->constrained('vehicle_service_job_cards')->cascadeOnDelete();
            // Link to the planned labor line
            $table->foreignId('labor_item_id')->constrained('vehicle_service_labor_items', 'id')->cascadeOnDelete();
            // Technician assigned to perform this work
            $table->foreignId('employee_id')->constrained('employees', 'id');
            // Actual hours worked on this specific labor item
            $table->decimal('hours_worked', 20, 4)->nullable();
            // Actual rate for this technician (may differ from the planned unit price)
            $table->decimal('hourly_rate', 20, 4)->nullable();
            // Incentive stored as both configuration and calculated amount
            $table->string('incentive_type')->nullable()->comment('percentage, fixed');
            $table->decimal('incentive_value', 20, 4)->default(0);
            $table->decimal('incentive_amount', 20, 4)->default(0)->comment('Calculated incentive amount');
            $table->string('split_type')->default('percentage')->comment('percentage, fixed');
            $table->decimal('split_value', 20, 4)->default(0);
            $table->decimal('split_amount', 20, 4)->default(0);
            $table->string('status')->default('assigned')->comment('assigned, in_progress, completed, cancelled');
            // Role of the technician (e.g., lead, assistant)
            $table->string('role')->nullable()->comment('lead, assistant, supervisor');
            $table->unsignedBigInteger('assigned_by')->nullable();
            $table->unsignedBigInteger('completed_by')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->unique(['tenant_id', 'labor_item_id', 'employee_id'], 'vehicle_service_labor_assignments_labor_item_employee_uk');
            $table->index(['tenant_id', 'employee_id'], 'vehicle_service_labor_assignments_employee_idx');
            $table->index(['tenant_id', 'status'], 'vehicle_service_labor_assignments_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_service_labor_assignments');
    }
};
