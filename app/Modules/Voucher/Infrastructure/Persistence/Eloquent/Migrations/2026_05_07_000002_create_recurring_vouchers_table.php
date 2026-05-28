<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('voucher_types', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')
                ->constrained('tenants')
                ->cascadeOnDelete();
            $table->foreignId('organization_unit_id')
                ->nullable()
                ->constrained('organization_units')
                ->nullOnDelete();
            $table->json('metadata')->nullable();

            $table->string('name');
            $table->string('code', 120);
            $table->string('direction')
                ->comment('payable, receivable, journal, transfer');
            $table->string('posting_behavior')->default('manual');
            $table->json('allowed_payment_methods')->nullable();
            $table->json('status_workflow')->nullable();

            $table->foreignId('default_cash_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('default_bank_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('default_expense_account_id')
                ->nullable()
                ->constrained('accounts')
                ->nullOnDelete();
            $table->foreignId('default_receivable_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('default_payable_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('default_tax_account_id')->nullable()->constrained('accounts')->nullOnDelete();

            $table->foreignId('document_type_id')->nullable()->constrained('document_types')->nullOnDelete();
            $table->foreignId('document_definition_id')->nullable()->constrained('document_definitions')->nullOnDelete();

            $table->boolean('requires_approval')->default(true);
            $table->boolean('allow_direct_posting')->default(false);
            $table->boolean('allow_reversal')->default(true);
            $table->boolean('allow_partial_allocation')->default(true);
            $table->boolean('is_active')->default(true);

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'organization_unit_id', 'code'], 'voucher_types_tenant_org_code_uk');
            $table->index(['tenant_id', 'is_active'], 'voucher_types_tenant_active_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voucher_types');
    }
};
