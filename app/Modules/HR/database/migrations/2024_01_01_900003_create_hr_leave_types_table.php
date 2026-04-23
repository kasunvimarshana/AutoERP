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
            $table->foreignId('tenant_id')->constrained('tenants', 'id', 'hr_leave_types_tenant_id_fk')->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 20);
            $table->text('description')->nullable();
            $table->decimal('max_days_per_year', 5, 2)->default(0);
            $table->decimal('carry_forward_days', 5, 2)->default(0);
            $table->boolean('is_paid')->default(true);
            $table->boolean('requires_approval')->default(true);
            $table->string('applicable_gender', 10)->nullable();
            $table->integer('min_service_days')->default(0);
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['tenant_id'], 'hr_leave_types_tenant_id_idx');
            $table->unique(['tenant_id', 'code'], 'hr_leave_types_tenant_code_uk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_leave_types');
    }
};
