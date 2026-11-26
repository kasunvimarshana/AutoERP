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
        Schema::create('hr_leave_types', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants', indexName: 'hr_leave_types_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->string('code', 32);
            $table->string('name', 100);
            $table->string('color', 16)->default('#10B981');
            $table->boolean('is_paid')->default(true);
            $table->boolean('requires_attachment')->default(false);
            $table->unsignedSmallInteger('attachment_mandatory_after_days')->default(2);
            $table->boolean('allow_half_day')->default(true);
            $table->boolean('allow_hourly')->default(false);
            $table->boolean('is_compensatory')->default(false);
            $table->boolean('sandwich_rule_applied')->default(false);
            $table->string('status', 32)->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['id', 'tenant_id'], 'hr_leave_types_id_tenant_uk');
            $table->unique(['tenant_id', 'code', 'deleted_at'], 'hr_leave_types_tenant_code_uk');
            $table->index(['tenant_id', 'status'], 'hr_leave_types_tenant_status_ix');

            $table->foreign(['organization_unit_id', 'tenant_id'], 'hr_leave_types_org_unit_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_leave_types');
    }
};
