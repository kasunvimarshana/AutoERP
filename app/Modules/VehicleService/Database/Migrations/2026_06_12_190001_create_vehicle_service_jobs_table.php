<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_service_jobs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
            $table->string('job_number', 100);
            $table->date('job_date');
            $table->date('expected_delivery_date')->nullable();
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->foreignId('vehicle_id')->constrained('vehicles')->restrictOnDelete();
            $table->foreignId('supervisor_employee_id')->nullable()->constrained('hr_employees')->nullOnDelete();
            $table->string('supervisor_commission_type', 20)->default('none');
            $table->decimal('supervisor_commission_value', 20, 6)->default('0.000000');
            $table->decimal('supervisor_commission_amount', 20, 6)->default('0.000000');
            $table->string('status', 30)->default('draft');
            $table->decimal('odometer_reading', 20, 6)->nullable();
            $table->string('fuel_level')->nullable();
            $table->string('priority', 30)->nullable();
            $table->decimal('subtotal', 20, 6)->default('0.000000');
            $table->decimal('discount_total', 20, 6)->default('0.000000');
            $table->decimal('tax_total', 20, 6)->default('0.000000');
            $table->decimal('charge_total', 20, 6)->default('0.000000');
            $table->decimal('grand_total', 20, 6)->default('0.000000');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('completed_by')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'job_number'], 'vehicle_service_jobs_tenant_number_uk');
            $table->index(['tenant_id', 'organization_unit_id'], 'vehicle_service_jobs_tenant_org_idx');
            $table->index(['customer_id', 'vehicle_id'], 'vehicle_service_jobs_party_vehicle_idx');
            $table->index(['status', 'job_date'], 'vehicle_service_jobs_status_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_service_jobs');
    }
};
