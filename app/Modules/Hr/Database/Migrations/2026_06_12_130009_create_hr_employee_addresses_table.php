<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_employee_addresses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id');
            $table->foreign('tenant_id', 'hr_employee_addresses_tenant_fk')
                ->references('id')
                ->on('tenants')
                ->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->foreign(['organization_unit_id', 'tenant_id'], 'hr_employee_addresses_org_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreignId('employee_id');
            $table->string('address_type');
            $table->string('address_line_1');
            $table->string('address_line_2')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('country')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['tenant_id', 'employee_id'], 'hr_employee_addresses_scope_ix');

            $table->unique(['id', 'tenant_id'], 'hr_employee_addresses_id_tenant_uk');
            $table->foreign(['employee_id', 'tenant_id'], 'hr_employee_addresses_employee_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('hr_employees')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_employee_addresses');
    }

};
