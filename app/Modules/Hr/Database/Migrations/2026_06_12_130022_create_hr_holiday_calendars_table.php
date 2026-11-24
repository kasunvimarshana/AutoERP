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
        Schema::create('hr_holiday_calendars', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants', indexName: 'hr_hol_cal_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->string('code', 32);
            $table->string('name', 100);
            $table->unsignedSmallInteger('calendar_year');
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->unique(['id', 'tenant_id'], 'hr_hol_cal_id_tenant_uk');
            $table->unique(['tenant_id', 'code'], 'hr_hol_cal_tenant_code_uk');
            $table->index(['tenant_id', 'calendar_year'], 'hr_hol_cal_year_ix');

            $table->foreign(['organization_unit_id', 'tenant_id'], 'hr_hol_cal_org_unit_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_holiday_calendars');
    }
};
