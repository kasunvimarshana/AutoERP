<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->json('metadata')->nullable();

            $table->string('name');
            $table->string('code');
            $table->string('shift_type')->default('regular');
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('break_duration')->default(0);
            $table->integer('grace_minutes')->default(0);
            $table->integer('overtime_threshold')->default(0);
            $table->json('work_days')->nullable();
            $table->boolean('is_night_shift')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'code'], 'shift_code_uk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shifts');
    }
};
