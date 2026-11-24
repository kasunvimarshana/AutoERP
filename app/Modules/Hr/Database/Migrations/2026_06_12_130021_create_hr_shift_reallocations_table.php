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
        Schema::create('hr_shift_reallocations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants', indexName: 'hr_shift_reallocations_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->foreignId('absent_employee_id');
            $table->foreignId('cover_employee_id');
            $table->foreignId('shift_id');
            $table->date('reallocation_date');
            $table->string('reason_code', 32)->default('sick_cover');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('reallocated_by')->nullable();
            $table->timestamps();

            $table->unique(['id', 'tenant_id'], 'hr_shift_reallocations_id_tenant_uk');
            $table->index(['reallocation_date', 'shift_id'], 'hr_shift_realloc_date_shift_ix');
            $table->index(['cover_employee_id', 'reallocation_date'], 'hr_shift_realloc_cover_date_ix');
            $table->index(['absent_employee_id', 'reallocation_date'], 'hr_shift_realloc_absent_date_ix');

            $table->foreign(['absent_employee_id', 'tenant_id'], 'hr_shift_realloc_absent_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('hr_employees')
                ->restrictOnDelete();
            $table->foreign(['cover_employee_id', 'tenant_id'], 'hr_shift_realloc_cover_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('hr_employees')
                ->restrictOnDelete();
            $table->foreign(['shift_id', 'tenant_id'], 'hr_shift_realloc_shift_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('hr_shifts')
                ->restrictOnDelete();
            $table->foreign(['organization_unit_id', 'tenant_id'], 'hr_shift_realloc_org_unit_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['reallocated_by', 'tenant_id'], 'hr_shift_realloc_user_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_shift_reallocations');
    }
};
