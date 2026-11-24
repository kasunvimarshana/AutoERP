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
        Schema::create('hr_shift_rotation_steps', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants', indexName: 'hr_rot_steps_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->unsignedBigInteger('shift_rotation_id');
            $table->unsignedInteger('day_sequence'); // 1 to cycle_days
            $table->unsignedBigInteger('shift_id')->nullable(); // null means rest / day off
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->unique(['id', 'tenant_id'], 'hr_rot_steps_id_tenant_uk');
            $table->unique(['tenant_id', 'shift_rotation_id', 'day_sequence'], 'hr_rot_steps_seq_uk');
            $table->index(['tenant_id', 'shift_id'], 'hr_rot_steps_shift_ix');

            $table->foreign(['shift_rotation_id', 'tenant_id'], 'hr_rot_steps_rot_fk')
                ->references(['id', 'tenant_id'])
                ->on('hr_shift_rotations')
                ->cascadeOnDelete();

            $table->foreign(['shift_id', 'tenant_id'], 'hr_rot_steps_shift_fk')
                ->references(['id', 'tenant_id'])
                ->on('hr_shifts')
                ->restrictOnDelete();

            $table->foreign(['organization_unit_id', 'tenant_id'], 'hr_rot_steps_org_unit_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_shift_rotation_steps');
    }
};
