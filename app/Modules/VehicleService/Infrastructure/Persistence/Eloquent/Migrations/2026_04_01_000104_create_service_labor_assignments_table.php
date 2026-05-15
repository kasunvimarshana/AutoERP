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
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->json('metadata')->nullable();

            $table->foreignId('labor_item_id')->constrained('service_labor_items')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('hr_employees');
            $table->string('role')->default('lead');
            $table->decimal('hours_worked', 8, 2)->nullable();
            $table->decimal('hourly_rate', 20, 4)->nullable();
            $table->decimal('incentive_amount', 20, 4)->default(0);
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->unique(['tenant_id', 'organization_unit_id', 'labor_item_id', 'employee_id'], 'service_labor_assignments_labor_item_employee_uk');
            $table->index(['tenant_id', 'organization_unit_id', 'employee_id'], 'service_labor_assignments_employee_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_labor_assignments');
    }
};
