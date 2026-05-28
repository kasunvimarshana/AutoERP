<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vouchers', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
            $table->json('metadata')->nullable();

            $table->foreignId('voucher_type_id')->constrained('voucher_types')->restrictOnDelete();
            $table->string('voucher_number');
            $table->date('voucher_date');
            $table->string('party_type')->nullable();
            $table->unsignedBigInteger('party_id')->nullable();
            $table->string('direction')->comment('payable, receivable, journal, transfer');

            $table->foreignId('currency_id')->nullable()->constrained('currencies')->nullOnDelete();
            $table->decimal('exchange_rate', 20, 6)->default(1);
            $table->decimal('total_debit', 20, 4)->default(0);
            $table->decimal('total_credit', 20, 4)->default(0);
            $table->decimal('total_amount', 20, 4)->default(0);
            $table->string('status')->default('draft');

            $table->foreignId('payment_method_id')->nullable()->constrained('payment_methods')->nullOnDelete();
            $table->foreignId('cash_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('bank_account_id')->nullable()->constrained('accounts')->nullOnDelete();

            $table->unsignedBigInteger('document_id')->nullable();
            $table->unsignedBigInteger('journal_entry_id')->nullable();
            $table->unsignedBigInteger('payment_id')->nullable();
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('remarks')->nullable();

            $table->unsignedBigInteger('original_voucher_id')->nullable();
            $table->unsignedBigInteger('reversal_voucher_id')->nullable();
            $table->text('reversal_reason')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('submitted_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->unsignedBigInteger('rejected_by')->nullable();
            $table->unsignedBigInteger('posted_by')->nullable();
            $table->unsignedBigInteger('cancelled_by')->nullable();
            $table->unsignedBigInteger('reversed_by')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('reversed_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'voucher_number'], 'vouchers_tenant_number_uk');
            $table->index(['tenant_id', 'voucher_type_id', 'voucher_date'], 'vouchers_type_date_idx');
            $table->index(['tenant_id', 'status', 'voucher_date'], 'vouchers_status_date_idx');
            $table->index(['tenant_id', 'party_type', 'party_id'], 'vouchers_party_idx');
            $table->index(['tenant_id', 'reference_type', 'reference_id'], 'vouchers_reference_idx');
            $table->foreign('original_voucher_id')->references('id')->on('vouchers')->nullOnDelete();
            $table->foreign('reversal_voucher_id')->references('id')->on('vouchers')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vouchers');
    }
};
