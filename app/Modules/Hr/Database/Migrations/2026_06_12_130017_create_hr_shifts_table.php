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
        Schema::create('hr_shifts', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants', indexName: 'hr_shifts_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->string('code', 32);
            $table->string('name', 100);
            $table->string('color', 16)->default('#3B82F6');
            $table->time('start_time');
            $table->time('end_time');
            $table->boolean('is_overnight')->default(false);
            $table->unsignedSmallInteger('grace_in_minutes')->default(15);
            $table->unsignedSmallInteger('grace_out_minutes')->default(15);
            $table->unsignedSmallInteger('half_day_threshold_minutes')->default(240);
            $table->unsignedSmallInteger('full_day_threshold_minutes')->default(480);
            $table->boolean('is_break_paid')->default(false);
            $table->boolean('auto_deduct_break')->default(false);
            $table->unsignedSmallInteger('auto_deduct_break_minutes')->default(0);
            $table->unsignedSmallInteger('auto_deduct_threshold_minutes')->default(300);
            $table->boolean('is_flexible')->default(false);
            $table->string('status', 32)->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['id', 'tenant_id'], 'hr_shifts_id_tenant_uk');
            $table->unique(['tenant_id', 'code'], 'hr_shifts_tenant_code_uk');
            $table->index(['tenant_id', 'status'], 'hr_shifts_tenant_status_ix');
            $table->index(['tenant_id', 'organization_unit_id'], 'hr_shifts_tenant_org_ix');

            $table->foreign(['organization_unit_id', 'tenant_id'], 'hr_shifts_org_unit_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_shifts');
    }
};
