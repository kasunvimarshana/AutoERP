<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_expenses', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->string('expense_number', 100);
            $table->foreignId('agreement_id')->nullable()->constrained('rental_agreements')->nullOnDelete();
            $table->foreignId('vehicle_allocation_id')->nullable()->constrained('rental_vehicle_allocations')->nullOnDelete();
            $table->foreignId('usage_log_id')->nullable()->constrained('rental_usage_logs')->nullOnDelete();
            $table->foreignId('vehicle_id')->constrained('vehicles')->restrictOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained('hr_employees')->nullOnDelete();
            $table->string('expense_type', 30);
            $table->date('expense_date');
            $table->foreignId('currency_id')->constrained('currencies')->restrictOnDelete();
            $table->decimal('net_amount', 20, 6);
            $table->foreignId('tax_group_id')->nullable()->constrained('tax_groups')->nullOnDelete();
            $table->decimal('tax_amount', 20, 6)->default('0.000000');
            $table->decimal('gross_amount', 20, 6);
            $table->string('reference_number', 100)->nullable();
            $table->text('description')->nullable();
            $table->string('source_document_type', 80)->nullable();
            $table->unsignedBigInteger('source_document_id')->nullable();
            $table->string('status', 30)->default('draft');
            $table->char('fingerprint', 64);
            $table->unsignedBigInteger('submitted_by')->nullable();
            $table->dateTime('submitted_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->unsignedBigInteger('rejected_by')->nullable();
            $table->dateTime('rejected_at')->nullable();
            $table->unsignedBigInteger('reversed_by')->nullable();
            $table->dateTime('reversed_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'expense_number'], 'rental_expenses_tenant_number_uk');
            $table->unique(['tenant_id', 'fingerprint'], 'rental_expenses_fingerprint_uk');
            $table->index(['vehicle_id', 'expense_date', 'status'], 'rental_expenses_vehicle_date_idx');
            $table->index(['agreement_id', 'expense_type', 'status'], 'rental_expenses_agreement_type_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_expenses');
    }
};
