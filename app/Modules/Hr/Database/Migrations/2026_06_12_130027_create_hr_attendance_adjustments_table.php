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
        Schema::create('hr_attendance_adjustments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', indexName: 'hr_att_adj_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->foreignId('attendance_record_id');
            $table->foreignId('regularization_id')->nullable();
            $table->unsignedBigInteger('adjusted_by');
            $table->string('previous_status', 32);
            $table->string('new_status', 32);
            $table->unsignedSmallInteger('previous_worked_minutes');
            $table->unsignedSmallInteger('new_worked_minutes');
            $table->unsignedSmallInteger('previous_overtime_minutes');
            $table->unsignedSmallInteger('new_overtime_minutes');
            $table->text('adjustment_reason');
            $table->timestamps();

            $table->unique(['id', 'tenant_id'], 'hr_att_adj_id_tenant_uk');
            $table->index(['attendance_record_id', 'created_at'], 'hr_att_adj_record_created_ix');
            $table->index(['tenant_id', 'adjusted_by'], 'hr_att_adj_tenant_user_ix');

            $table->foreign(['attendance_record_id', 'tenant_id'], 'hr_att_adj_record_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('hr_attendance_records')
                ->restrictOnDelete();
            $table->foreign(['regularization_id', 'tenant_id'], 'hr_att_adj_reg_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('hr_attendance_regularizations')
                ->restrictOnDelete();
            $table->foreign(['adjusted_by', 'tenant_id'], 'hr_att_adj_user_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
            $table->foreign(['organization_unit_id', 'tenant_id'], 'hr_att_adj_org_unit_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_attendance_adjustments');
    }
};
