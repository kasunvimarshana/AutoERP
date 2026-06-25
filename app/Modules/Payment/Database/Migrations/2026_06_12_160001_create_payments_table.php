<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->string('payment_number', 100);
            $table->enum('payment_type', [
                'supplier_payment',
                'customer_receipt',
                'service_receipt',
                'rental_receipt',
                'advance',
                'refund',
                'manual',
            ]);
            $table->enum('direction', ['inbound', 'outbound']);
            $table->string('party_type')->nullable();
            $table->unsignedBigInteger('party_id')->nullable();
            $table->string('source_type', 150)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('document_status', 50)->default('draft');
            $table->string('allocation_status', 50)->default('unallocated');
            $table->string('posting_status', 50)->default('not_posted');
            $table->string('instrument_status', 50)->nullable();
            $table->date('payment_date');
            $table->foreignId('currency_id')->nullable()->constrained('currencies', 'id')->nullOnDelete();
            $table->decimal('exchange_rate', 20, 6)->default('1.000000');
            $table->string('reference_number')->nullable();
            $table->string('cheque_number', 100)->nullable();
            $table->date('cheque_date')->nullable();
            $table->foreignId('bank_account_id')->nullable();
            $table->string('payee_name')->nullable();
            $table->text('amount_in_words')->nullable();
            $table->enum('status', [
                'draft',
                'pending_approval',
                'approved',
                'posted',
                'voided',
                'reversed',
                'cancelled',
                'partially_allocated',
                'fully_allocated',
                'allocated',
                'refunded',
                'void',
            ])->default('draft');
            $table->decimal('total_amount', 20, 6)->default('0');
            $table->decimal('allocated_amount', 20, 6)->default('0');
            $table->decimal('unapplied_amount', 20, 6)->default('0');
            $table->decimal('refunded_amount', 20, 6)->default('0');
            $table->foreignId('finance_journal_entry_id')->nullable();
            $table->string('posting_correlation_key', 160)->nullable();
            $table->unsignedBigInteger('reversed_by')->nullable();
            $table->timestamp('reversed_at')->nullable();
            $table->text('reversal_reason')->nullable();
            $table->foreignId('reversal_payment_id')->nullable();
            $table->foreignId('original_payment_id')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->unsignedBigInteger('voided_by')->nullable();
            $table->timestamp('voided_at')->nullable();
            $table->text('void_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'payment_number'], 'payments_tenant_number_uk');
            $table->index(['tenant_id', 'organization_unit_id'], 'payments_tenant_org_idx');
            $table->index(['payment_type', 'direction', 'status'], 'payments_type_direction_status_idx');
            $table->index(['document_status', 'allocation_status', 'posting_status'], 'payments_status_dimensions_idx');
            $table->index(['party_type', 'party_id'], 'payments_party_idx');
            $table->index(['source_type', 'source_id'], 'payments_source_idx');
            $table->index('cheque_number', 'payments_cheque_number_idx');
            $table->index('payment_date', 'payments_date_idx');
            $table->unique('posting_correlation_key', 'payments_posting_correlation_uk');
            $table->index('finance_journal_entry_id', 'payments_finance_journal_idx');
            $table->index('original_payment_id', 'payments_original_payment_idx');

            $table->unique(['id', 'tenant_id'], 'payments_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'payments_organization_unit_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['bank_account_id', 'tenant_id'], 'payments_bank_account_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('finance_accounts')
                ->restrictOnDelete();
            $table->foreign(['finance_journal_entry_id', 'tenant_id'], 'payments_finance_journal_entry_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('finance_journal_entries')
                ->restrictOnDelete();
            $table->foreign(['reversal_payment_id', 'tenant_id'], 'payments_reversal_payment_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('payments')
                ->restrictOnDelete();
            $table->foreign(['original_payment_id', 'tenant_id'], 'payments_original_payment_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('payments')
                ->restrictOnDelete();

            $table->foreign(['reversed_by', 'tenant_id'], 'payments_reversed_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
            $table->foreign(['created_by', 'tenant_id'], 'payments_created_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
            $table->foreign(['approved_by', 'tenant_id'], 'payments_approved_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
            $table->foreign(['voided_by', 'tenant_id'], 'payments_voided_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
