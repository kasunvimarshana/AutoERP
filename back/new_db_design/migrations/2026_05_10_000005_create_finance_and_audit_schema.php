<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units')->nullOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->string('account_code', 50);
            $table->string('account_name', 150);
            $table->string('account_type', 30);
            $table->string('account_subtype', 50)->nullable();
            $table->string('normal_balance', 10);
            $table->boolean('is_control_account')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['tenant_id', 'account_code']);
            $table->index(['tenant_id', 'parent_id']);
            $table->index(['tenant_id', 'account_type']);
            $table->index(['tenant_id', 'is_active']);
        });

        Schema::create('fiscal_years', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('year_name', 50);
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status_code', 30)->default('open');
            $table->timestamps();

            $table->unique(['tenant_id', 'year_name']);
        });

        Schema::create('fiscal_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('fiscal_year_id')->constrained('fiscal_years')->cascadeOnDelete();
            $table->unsignedTinyInteger('period_number');
            $table->string('period_name', 50);
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status_code', 30)->default('open');
            $table->timestamps();

            $table->unique(['tenant_id', 'fiscal_year_id', 'period_number']);
            $table->index(['tenant_id', 'status_code', 'start_date']);
        });

        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('fiscal_period_id')->constrained('fiscal_periods')->cascadeOnDelete();
            $table->foreignId('reversal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('entry_number', 100);
            $table->string('entry_type', 30)->default('manual');
            $table->string('reference_type', 100)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('description')->nullable();
            $table->date('entry_date');
            $table->date('posting_date')->nullable();
            $table->string('status_code', 30)->default('draft');
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'entry_number']);
            $table->index(['tenant_id', 'fiscal_period_id', 'status_code', 'posting_date'], 'journal_entries_period_status_post_idx');
            $table->index(['tenant_id', 'reference_type', 'reference_id']);
            $table->index(['tenant_id', 'entry_date']);
        });

        Schema::create('journal_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('journal_entry_id')->constrained('journal_entries')->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('accounts')->cascadeOnDelete();
            $table->foreignId('party_id')->nullable()->constrained('parties')->nullOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units')->nullOnDelete();
            $table->foreignId('currency_id')->nullable()->constrained('currencies')->nullOnDelete();
            $table->unsignedInteger('line_no');
            $table->decimal('exchange_rate', 20, 10)->default(1);
            $table->decimal('debit_amount', 20, 6)->default(0);
            $table->decimal('credit_amount', 20, 6)->default(0);
            $table->decimal('base_debit_amount', 20, 6)->default(0);
            $table->decimal('base_credit_amount', 20, 6)->default(0);
            $table->timestamps();

            $table->index(['tenant_id', 'journal_entry_id', 'line_no']);
            $table->index(['tenant_id', 'account_id', 'journal_entry_id']);
            $table->index(['tenant_id', 'party_id']);
            $table->index(['tenant_id', 'org_unit_id']);
        });

        Schema::create('subledger_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('party_id')->constrained('parties')->cascadeOnDelete();
            $table->foreignId('currency_id')->constrained('currencies')->cascadeOnDelete();
            $table->foreignId('source_document_id')->nullable()->constrained('commercial_documents')->nullOnDelete();
            $table->string('subledger_type', 30);
            $table->string('document_number', 100);
            $table->date('document_date');
            $table->date('due_date')->nullable();
            $table->decimal('original_amount', 20, 6);
            $table->decimal('open_amount', 20, 6);
            $table->string('status_code', 30)->default('open');
            $table->timestamps();

            $table->unique(['tenant_id', 'subledger_type', 'document_number'], 'subledger_documents_scope_uk');
            $table->index(['tenant_id', 'party_id', 'status_code', 'due_date'], 'subledger_documents_party_status_due_idx');
            $table->index(['tenant_id', 'source_document_id']);
            $table->index(['tenant_id', 'document_date']);
        });

        Schema::create('subledger_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('subledger_document_id')->constrained('subledger_documents')->cascadeOnDelete();
            $table->string('reference_type', 100);
            $table->unsignedBigInteger('reference_id');
            $table->string('allocation_type', 50)->default('payment');
            $table->decimal('allocated_amount', 20, 6);
            $table->timestamp('allocated_at');
            $table->timestamps();

            $table->index(['tenant_id', 'subledger_document_id']);
            $table->index(['tenant_id', 'reference_type', 'reference_id']);
            $table->index(['tenant_id', 'allocated_at']);
        });

        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('accounts')->cascadeOnDelete();
            $table->foreignId('currency_id')->nullable()->constrained('currencies')->nullOnDelete();
            $table->string('bank_code', 50)->nullable();
            $table->string('bank_name', 150);
            $table->string('account_name', 150);
            $table->string('account_number_masked', 100);
            $table->string('iban', 64)->nullable();
            $table->string('status_code', 30)->default('active');
            $table->timestamps();

            $table->unique(['tenant_id', 'account_number_masked']);
            $table->index(['tenant_id', 'account_id']);
            $table->index(['tenant_id', 'status_code']);
        });

        Schema::create('bank_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('bank_account_id')->constrained('bank_accounts')->cascadeOnDelete();
            $table->string('external_reference', 150)->nullable();
            $table->date('transaction_date');
            $table->date('value_date')->nullable();
            $table->string('description', 255);
            $table->decimal('amount', 20, 6);
            $table->decimal('balance_after', 20, 6)->nullable();
            $table->string('direction_code', 20);
            $table->string('reconciliation_status_code', 30)->default('unmatched');
            $table->timestamps();

            $table->unique(['tenant_id', 'bank_account_id', 'external_reference'], 'bank_transactions_external_ref_uk');
            $table->index(['tenant_id', 'bank_account_id', 'transaction_date'], 'bank_transactions_account_date_idx');
            $table->index(['tenant_id', 'reconciliation_status_code', 'transaction_date'], 'bank_transactions_reconcile_date_idx');
            $table->index(['tenant_id', 'value_date']);
        });

        Schema::create('bank_reconciliations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('bank_account_id')->constrained('bank_accounts')->cascadeOnDelete();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('opening_balance', 20, 6);
            $table->decimal('closing_balance', 20, 6);
            $table->string('status_code', 30)->default('draft');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'bank_account_id', 'period_end']);
            $table->index(['tenant_id', 'status_code']);
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('party_id')->nullable()->constrained('parties')->nullOnDelete();
            $table->foreignId('currency_id')->constrained('currencies')->cascadeOnDelete();
            $table->foreignId('bank_account_id')->nullable()->constrained('bank_accounts')->nullOnDelete();
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->string('payment_number', 100);
            $table->string('payment_direction', 20);
            $table->string('payment_method', 50);
            $table->string('status_code', 30)->default('draft');
            $table->date('payment_date');
            $table->decimal('amount', 20, 6);
            $table->string('reference_number', 150)->nullable();
            $table->string('idempotency_key', 150)->nullable();
            $table->text('memo')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'payment_number']);
            $table->unique(['tenant_id', 'idempotency_key']);
            $table->index(['tenant_id', 'party_id', 'payment_date']);
            $table->index(['tenant_id', 'status_code', 'payment_date']);
            $table->index(['tenant_id', 'bank_account_id', 'payment_date']);
        });

        Schema::create('payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('payment_id')->constrained('payments')->cascadeOnDelete();
            $table->foreignId('commercial_document_id')->nullable()->constrained('commercial_documents')->nullOnDelete();
            $table->foreignId('subledger_document_id')->nullable()->constrained('subledger_documents')->nullOnDelete();
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->decimal('amount', 20, 6);
            $table->timestamp('allocated_at');
            $table->timestamps();

            $table->index(['tenant_id', 'payment_id']);
            $table->index(['tenant_id', 'commercial_document_id']);
            $table->index(['tenant_id', 'subledger_document_id']);
            $table->index(['tenant_id', 'allocated_at']);
        });

        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('attachable_type', 120);
            $table->unsignedBigInteger('attachable_id');
            $table->string('disk', 50);
            $table->string('path', 255);
            $table->string('original_name', 255);
            $table->string('mime_type', 120);
            $table->unsignedBigInteger('size_bytes');
            $table->string('checksum', 128)->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'attachable_type', 'attachable_id']);
            $table->index(['tenant_id', 'path']);
            $table->index(['tenant_id', 'uploaded_by']);
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('auditable_type', 120)->nullable();
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->string('action_code', 50);
            $table->string('event_name', 100)->nullable();
            $table->text('summary')->nullable();
            $table->json('old_values_json')->nullable();
            $table->json('new_values_json')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('context_json')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['tenant_id', 'occurred_at']);
            $table->index(['tenant_id', 'action_code', 'occurred_at']);
            $table->index(['tenant_id', 'auditable_type', 'auditable_id']);
            $table->index(['tenant_id', 'user_id', 'occurred_at']);
        });

        Schema::create('integration_outbox', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->nullOnDelete();
            $table->string('event_name', 100);
            $table->string('aggregate_type', 120);
            $table->unsignedBigInteger('aggregate_id');
            $table->json('payload_json')->nullable();
            $table->string('status_code', 30)->default('pending');
            $table->timestamp('available_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->index(['status_code', 'available_at']);
            $table->index(['tenant_id', 'aggregate_type', 'aggregate_id']);
            $table->index(['tenant_id', 'processed_at']);
        });

        Schema::create('integration_inbox', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->nullOnDelete();
            $table->string('source_system', 100);
            $table->string('message_key', 150);
            $table->json('payload_json')->nullable();
            $table->string('status_code', 30)->default('received');
            $table->text('error_message')->nullable();
            $table->timestamp('received_at');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->unique(['source_system', 'message_key']);
            $table->index(['tenant_id', 'received_at']);
            $table->index(['tenant_id', 'status_code', 'received_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_inbox');
        Schema::dropIfExists('integration_outbox');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('attachments');
        Schema::dropIfExists('payment_allocations');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('bank_reconciliations');
        Schema::dropIfExists('bank_transactions');
        Schema::dropIfExists('bank_accounts');
        Schema::dropIfExists('subledger_allocations');
        Schema::dropIfExists('subledger_documents');
        Schema::dropIfExists('journal_lines');
        Schema::dropIfExists('journal_entries');
        Schema::dropIfExists('fiscal_periods');
        Schema::dropIfExists('fiscal_years');
        Schema::dropIfExists('accounts');
    }
};
