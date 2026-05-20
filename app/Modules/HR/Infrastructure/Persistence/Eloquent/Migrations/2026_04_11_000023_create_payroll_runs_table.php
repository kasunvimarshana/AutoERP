<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_runs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->json('metadata')->nullable();

            $table->date('period_start');
            $table->date('period_end');
            $table->date('payment_date');
            $table->string('status')->default('draft');
            $table->decimal('total_gross', 20, 4)->default(0);
            $table->decimal('total_deductions', 20, 4)->default(0);
            $table->decimal('total_net', 20, 4)->default(0);
            $table->decimal('total_employer_contributions', 20, 4)->default(0);
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'status', 'period_end'], 'payroll_runs_status_period_end_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_runs');
    }
};
