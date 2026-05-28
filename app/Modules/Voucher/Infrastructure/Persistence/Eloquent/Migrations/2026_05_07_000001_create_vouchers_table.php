<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('voucher_settings', function (Blueprint $table): void {
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

            $table->foreignId('default_cash_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('default_bank_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('default_expense_account_id')
                ->nullable()
                ->constrained('accounts')
                ->nullOnDelete();
            $table->foreignId('default_advance_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('default_receivable_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('default_payable_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('default_write_off_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('default_tax_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('default_currency_id')->nullable()->constrained('currencies')->nullOnDelete();
            $table->foreignId('default_payment_method_id')->nullable()->constrained('payment_methods')->nullOnDelete();

            $table->foreignId('default_document_definition_id')
                ->nullable()
                ->constrained('document_definitions')
                ->nullOnDelete();
            $table->foreignId('payment_voucher_document_definition_id')
                ->nullable()
                ->constrained('document_definitions')
                ->nullOnDelete();
            $table->foreignId('receipt_voucher_document_definition_id')
                ->nullable()
                ->constrained('document_definitions')
                ->nullOnDelete();
            $table->foreignId('journal_voucher_document_definition_id')
                ->nullable()
                ->constrained('document_definitions')
                ->nullOnDelete();
            $table->foreignId('contra_voucher_document_definition_id')
                ->nullable()
                ->constrained('document_definitions')
                ->nullOnDelete();
            $table->foreignId('expense_voucher_document_definition_id')
                ->nullable()
                ->constrained('document_definitions')
                ->nullOnDelete();

            $table->boolean('require_approval')->default(true);
            $table->boolean('allow_direct_posting')->default(false);
            $table->boolean('allow_reversal')->default(true);
            $table->boolean('allow_partial_allocation')->default(true);
            $table->string('default_sequence_period_type')->default('yearly');
            $table->boolean('is_active')->default(true);

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'organization_unit_id'], 'voucher_settings_tenant_org_uk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voucher_settings');
    }
};
