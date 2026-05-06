<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_payroll_runs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');

            $table->date('period_start');
            $table->date('period_end');
            $table->string('status', 20)->default('draft');
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->decimal('total_gross', 20, 6)->default(0);
            $table->decimal('total_deductions', 20, 6)->default(0);
            $table->decimal('total_net', 20, 6)->default(0);
            $table->json('metadata')->nullable();
            $table->foreign('approved_by', 'hr_payroll_runs_approved_by_fk')
                ->references('id')->on('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id'], 'hr_payroll_runs_tenant_id_idx');
            $table->index(['tenant_id', 'status', 'period_end'], 'hr_payroll_runs_tenant_status_period_end_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_payroll_runs');
    }
};
