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
        Schema::create('hr_shift_breaks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', indexName: 'hr_shift_breaks_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->foreignId('shift_id');
            $table->string('name', 50);
            $table->string('break_type', 32)->default('meal');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->unsignedSmallInteger('duration_minutes');
            $table->boolean('is_paid')->default(false);
            $table->boolean('is_mandatory')->default(true);
            $table->timestamps();

            $table->unique(['id', 'tenant_id'], 'hr_shift_breaks_id_tenant_uk');
            $table->index(['shift_id', 'break_type'], 'hr_shift_breaks_shift_type_ix');

            $table->foreign(['shift_id', 'tenant_id'], 'hr_shift_breaks_shift_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('hr_shifts')
                ->cascadeOnDelete();
            $table->foreign(['organization_unit_id', 'tenant_id'], 'hr_shift_breaks_org_unit_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_shift_breaks');
    }
};
