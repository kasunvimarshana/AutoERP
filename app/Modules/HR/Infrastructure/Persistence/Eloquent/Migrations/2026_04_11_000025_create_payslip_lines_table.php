<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payslip_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete()->comment('Multi-tenant owner reference');
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete()->comment('Branch or department ownership');
            $table->json('metadata')->nullable()->comment('Extensible custom dynamic data');

            $table->foreignId('payslip_id')->constrained('payslips')->cascadeOnDelete();
            $table->foreignId('salary_component_id')->nullable()->constrained('salary_components')->nullOnDelete();
            $table->string('item_name');
            $table->string('item_code');
            $table->string('type')->default('earning');
            $table->decimal('amount', 20, 4)->default(0);
            $table->unsignedInteger('sequence')->default(0);

            $table->timestamps();

            $table->index(['tenant_id', 'payslip_id'], 'payslip_lines_payslip_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payslip_lines');
    }
};
