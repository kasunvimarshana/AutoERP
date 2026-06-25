<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_employee_status_histories', function (Blueprint $table): void {
            $table->id();
            $this->scope($table, 'hr_emp_status_history');
            $table->foreignId('employee_id');
            $table->string('old_status')->nullable();
            $table->string('new_status');
            $table->text('reason')->nullable();
            $table->unsignedBigInteger('changed_by')->nullable();
            $table->timestamp('changed_at');
            $table->timestamps();
            $table->index(['tenant_id', 'employee_id', 'changed_at'], 'hr_employee_status_history_idx');

            $table->unique(['id', 'tenant_id'], 'hr_employee_status_histories_id_tenant_uk');
            $table->foreign(['employee_id', 'tenant_id'], 'hr_employee_status_histories_employee_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('hr_employees')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_employee_status_histories');
    }

    private function scope(Blueprint $table, string $constraintPrefix): void
    {
        $table->foreignId('tenant_id');
        $table->foreign('tenant_id', $constraintPrefix.'_tenant_fk')
            ->references('id')
            ->on('tenants')
            ->restrictOnDelete();
        $table->foreignId('organization_unit_id')->nullable();
        $table->foreign(['organization_unit_id', 'tenant_id'], $constraintPrefix.'_org_tenant_fk')
            ->references(['id', 'tenant_id'])
            ->on('organization_units')
            ->restrictOnDelete();
    }
};
