<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_policy_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete()->comment('Multi-tenant owner reference');
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete()->comment('Branch or department ownership');
            $table->json('metadata')->nullable()->comment('Extensible custom dynamic data');

            $table->foreignId('leave_policy_id')->constrained('leave_policies')->cascadeOnDelete();
            $table->foreignId('leave_type_id')->constrained('leave_types');
            $table->decimal('annual_allocation', 20, 4);
            $table->string('accrual_type')->default('annual');
            $table->decimal('accrual_amount', 20, 4)->default(0);
            $table->decimal('carry_forward_max', 20, 4)->default(0);

            $table->timestamps();

            $table->unique(['tenant_id', 'leave_policy_id', 'leave_type_id'], 'leave_policy_lines_policy_type_uk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_policy_lines');
    }
};
