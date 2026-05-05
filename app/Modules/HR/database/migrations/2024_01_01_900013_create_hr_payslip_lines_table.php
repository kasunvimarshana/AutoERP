<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_payslip_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('payslip_id')->constrained('hr_payslips', 'id', 'hr_payslip_lines_payslip_id_fk')->cascadeOnDelete();
            $table->foreignId('payroll_item_id')->nullable()->constrained('hr_payroll_items', 'id', 'hr_payslip_lines_payroll_item_id_fk')->nullOnDelete();
            $table->string('item_name');
            $table->string('item_code', 20);
            $table->string('type', 20)->default('earning');
            $table->decimal('amount', 20, 6)->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['payslip_id'], 'hr_payslip_lines_payslip_id_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_payslip_lines');
    }
};
