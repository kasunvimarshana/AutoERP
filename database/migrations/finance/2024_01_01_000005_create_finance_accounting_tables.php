<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Chart of Accounts (COA)
        Schema::create('accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('organisation_id')->constrained()->cascadeOnDelete();
            $table->string('code')->index(); // Account code, e.g., 1010
            $table->string('name');
            $table->enum('type', ['ASSET', 'LIABILITY', 'EQUITY', 'REVENUE', 'EXPENSE'])->index();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['tenant_id', 'code']);
        });

        // 2. Journal Entries (Master Record)
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('organisation_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('branch_id')->constrained()->cascadeOnDelete();
            $table->string('reference_id')->index(); // Order ID, Invoice ID, etc.
            $table->string('reference_type')->index();
            $table->text('description')->nullable();
            $table->string('currency_code', 3)->default('USD');
            $table->decimal('exchange_rate', 20, 10)->default(1.0);
            $table->timestamp('entry_date')->index();
            $table->foreignUuid('user_id')->constrained();
            $table->timestamps();
        });

        // 3. Transaction Lines (Double-Entry Bookkeeping)
        Schema::create('transaction_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('journal_entry_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('account_id')->constrained();
            $table->decimal('debit', 20, 4)->default(0);
            $table->decimal('credit', 20, 4)->default(0);
            $table->decimal('base_debit', 20, 4)->default(0); // Amount in base currency
            $table->decimal('base_credit', 20, 4)->default(0);
            $table->text('memo')->nullable();
            $table->timestamps();

            // Double-entry check (debit == credit) usually enforced in the service layer
            $table->index(['tenant_id', 'account_id', 'created_at']);
        });

        // 4. Currencies & Exchange Rates
        Schema::create('currencies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('code', 3)->index();
            $table->string('name');
            $table->string('symbol', 5);
            $table->decimal('current_exchange_rate', 20, 10)->default(1.0);
            $table->boolean('is_base')->default(false);
            $table->timestamps();

            $table->unique(['tenant_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('currencies');
        Schema::dropIfExists('transaction_lines');
        Schema::dropIfExists('journal_entries');
        Schema::dropIfExists('accounts');
    }
};
