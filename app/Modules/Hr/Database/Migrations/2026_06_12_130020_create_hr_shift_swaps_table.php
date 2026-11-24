<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_shift_swaps', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants', indexName: 'hr_shift_swaps_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->foreignId('requester_employee_id');
            $table->foreignId('target_employee_id');
            $table->foreignId('requester_shift_id')->nullable();
            $table->foreignId('target_shift_id')->nullable();
            $table->date('swap_date');
            $table->date('target_swap_date');
            $table->text('reason');
            $table->string('status', 32)->default('pending_peer');
            $table->timestamp('peer_actioned_at')->nullable();
            $table->unsignedBigInteger('manager_actioned_by')->nullable();
            $table->timestamp('manager_actioned_at')->nullable();
            $table->text('manager_notes')->nullable();
            $table->timestamps();

            $table->unique(['id', 'tenant_id'], 'hr_shift_swaps_id_tenant_uk');
            $table->index(['requester_employee_id', 'status'], 'hr_shift_swaps_req_status_ix');
            $table->index(['target_employee_id', 'status'], 'hr_shift_swaps_tar_status_ix');
            $table->index(['swap_date', 'target_swap_date'], 'hr_shift_swaps_dates_ix');

            $table->foreign(['requester_employee_id', 'tenant_id'], 'hr_shift_swaps_req_emp_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('hr_employees')
                ->restrictOnDelete();
            $table->foreign(['target_employee_id', 'tenant_id'], 'hr_shift_swaps_tar_emp_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('hr_employees')
                ->restrictOnDelete();
            $table->foreign(['requester_shift_id', 'tenant_id'], 'hr_shift_swaps_req_shift_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('hr_shifts')
                ->restrictOnDelete();
            $table->foreign(['target_shift_id', 'tenant_id'], 'hr_shift_swaps_tar_shift_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('hr_shifts')
                ->restrictOnDelete();
            $table->foreign(['organization_unit_id', 'tenant_id'], 'hr_shift_swaps_org_unit_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['manager_actioned_by', 'tenant_id'], 'hr_shift_swaps_manager_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_shift_swaps');
    }
};
