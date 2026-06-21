<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_expense_allocations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->foreignId('expense_id')->constrained('rental_expenses')->cascadeOnDelete();
            $table->unsignedInteger('sequence');
            $table->string('allocation_type', 40);
            $table->foreignId('target_agreement_id')->nullable()->constrained('rental_agreements')->nullOnDelete();
            $table->foreignId('target_vehicle_allocation_id')->nullable()->constrained('rental_vehicle_allocations')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained('hr_employees')->nullOnDelete();
            $table->decimal('net_amount', 20, 6);
            $table->foreignId('tax_group_id')->nullable()->constrained('tax_groups')->nullOnDelete();
            $table->decimal('tax_amount', 20, 6)->default('0.000000');
            $table->decimal('withholding_amount', 20, 6)->default('0.000000');
            $table->decimal('markup_amount', 20, 6)->default('0.000000');
            $table->decimal('total_amount', 20, 6);
            $table->string('status', 30)->default('draft');
            $table->char('fingerprint', 64);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['expense_id', 'sequence'], 'rental_expense_allocations_sequence_uk');
            $table->unique(['tenant_id', 'fingerprint'], 'rental_expense_allocations_fingerprint_uk');
            $table->index(['allocation_type', 'status'], 'rental_expense_allocations_type_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_expense_allocations');
    }
};
