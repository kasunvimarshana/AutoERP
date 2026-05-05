<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_leave_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('leave_type_id')->constrained('hr_leave_types', 'id', 'hr_leave_policies_leave_type_id_fk')->cascadeOnDelete();
            $table->string('name');
            $table->string('accrual_type', 20)->default('annual');
            $table->decimal('accrual_amount', 8, 4)->default(0);
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['tenant_id'], 'hr_leave_policies_tenant_id_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_leave_policies');
    }
};
