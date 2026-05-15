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
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->json('metadata')->nullable();

            $table->foreignId('salary_component_id')->nullable()->constrained('hr_salary_components')->nullOnDelete();
            $table->string('item_name');
            $table->string('item_code', 20);
            $table->string('type', 20)->default('earning');
            $table->decimal('amount', 20, 4)->default(0);
            $table->unsignedInteger('sequence')->default(0);

            $table->timestamps();

            $table->index(['tenant_id', 'organization_unit_id', 'payslip_id'], 'hr_payslip_lines_payslip_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_payslip_lines');
    }
};
