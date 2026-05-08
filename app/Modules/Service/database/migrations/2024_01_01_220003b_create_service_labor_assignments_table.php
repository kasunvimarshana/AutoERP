<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_labor_assignments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');

            // Link to the planned labour line
            $table->foreignId('labor_item_id')->constrained('service_labor_items', 'id', 'assignments_labor_item_fk')->cascadeOnDelete();

            // Technician assigned to perform this work
            $table->foreignId('employee_id')->constrained('employees', 'id');

            // Actual hours worked on this specific labour item
            $table->decimal('hours_worked', 8, 2)->nullable();

            // Actual rate for this technician (may differ from the planned unit price)
            $table->decimal('hourly_rate', 20, 6)->nullable();

            // Commission/incentive for this specific assignment
            $table->enum('incentive_type', ['percentage', 'fixed'])->nullable();
            $table->decimal('incentive_value', 10, 6)->nullable();
            $table->decimal('incentive_amount', 20, 6)->default(0);

            // Role of the technician (e.g., lead, assistant)
            $table->string('role')->nullable()->comment('lead, assistant, supervisor');

            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Prevent duplicate assignment of the same employee to the same labour item
            $table->unique(['tenant_id', 'org_unit_id', 'labor_item_id', 'employee_id'], 'labor_assignments_uk');
            $table->index(['tenant_id', 'employee_id'], 'labor_assignments_employee_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_labor_assignments');
    }
};
