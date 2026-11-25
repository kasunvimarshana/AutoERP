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
        Schema::create('hr_holidays', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', indexName: 'hr_holidays_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->foreignId('calendar_id');
            $table->string('name', 100);
            $table->date('holiday_date');
            $table->boolean('is_mandatory')->default(true);
            $table->boolean('is_comp_off_eligible')->default(true);
            $table->decimal('overtime_multiplier', 5, 2)->default('2.00');
            $table->text('description')->nullable();
            $table->unsignedInteger('row_version')->default(1);
            $table->timestamps();

            $table->unique(['id', 'tenant_id'], 'hr_holidays_id_tenant_uk');
            $table->unique(['calendar_id', 'holiday_date'], 'hr_holidays_cal_date_uk');
            $table->index(['tenant_id', 'holiday_date'], 'hr_holidays_tenant_date_ix');

            $table->foreign(['calendar_id', 'tenant_id'], 'hr_holidays_cal_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('hr_holiday_calendars')
                ->cascadeOnDelete();
            $table->foreign(['organization_unit_id', 'tenant_id'], 'hr_holidays_org_unit_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_holidays');
    }
};
