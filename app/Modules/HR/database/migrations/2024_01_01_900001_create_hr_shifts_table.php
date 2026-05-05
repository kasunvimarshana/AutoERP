<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->string('name');
            $table->string('code', 20);
            $table->string('shift_type', 20)->default('regular');
            $table->string('start_time', 10);
            $table->string('end_time', 10);
            $table->integer('break_duration')->default(0);
            $table->json('work_days')->nullable();
            $table->integer('grace_minutes')->default(0);
            $table->integer('overtime_threshold')->default(0);
            $table->boolean('is_night_shift')->default(false);
            $table->json('metadata')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id'], 'hr_shifts_tenant_id_idx');
            $table->unique(['tenant_id', 'org_unit_id', 'code'], 'hr_shifts_tenant_code_uk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_shifts');
    }
};
