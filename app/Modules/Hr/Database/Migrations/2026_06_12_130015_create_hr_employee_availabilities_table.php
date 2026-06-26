<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_employee_availabilities', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id');
            $table->foreign('tenant_id', 'hr_employee_availabilities_tenant_fk')
                ->references('id')
                ->on('tenants')
                ->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->foreign(['organization_unit_id', 'tenant_id'], 'hr_employee_availabilities_org_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreignId('employee_id');
            $table->date('availability_date')->nullable();
            $table->string('availability_status');
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->text('reason')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'employee_id', 'availability_status'], 'hr_employee_availability_lookup_ix');
            $table->index(['source_type', 'source_id'], 'hr_employee_availability_source_ix');
            $table->index(['starts_at', 'ends_at'], 'hr_employee_availability_dates_ix');

            $table->unique(['id', 'tenant_id'], 'hr_employee_availabilities_id_tenant_uk');
            $table->foreign(['employee_id', 'tenant_id'], 'hr_employee_availabilities_employee_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('hr_employees')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_employee_availabilities');
    }

};
