<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_leave_types', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->json('metadata')->nullable();

            $table->string('name');
            $table->string('code', 20);
            $table->text('description')->nullable();
            $table->boolean('is_paid')->default(true);
            $table->boolean('requires_approval')->default(true);
            $table->boolean('is_active')->default(true);
            $table->decimal('max_days_per_year', 5, 2)->nullable();
            $table->decimal('carry_forward_max', 5, 2)->default(0);
            $table->boolean('allow_negative_balance')->default(false);
            $table->string('applicable_gender', 10)->nullable();
            $table->integer('min_service_days')->default(0);
            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'organization_unit_id', 'code'], 'hr_leave_types_code_uk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_leave_types');
    }
};
