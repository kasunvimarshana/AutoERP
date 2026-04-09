<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Bank Accounts ─────────────────────────────────────────────────────
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('account_id');   // linked GL account (chart_of_accounts)
            $table->string('name');
            $table->string('account_number', 100)->nullable();
            $table->string('bank_name')->nullable();
            $table->string('iban', 50)->nullable();
            $table->string('swift', 20)->nullable();
            $table->enum('type', ['bank', 'credit_card', 'cash', 'other'])->default('bank');
            $table->unsignedBigInteger('currency_id');
            $table->decimal('opening_balance', 18, 4)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('account_id')->references('id')->on('chart_of_accounts');
            $table->foreign('currency_id')->references('id')->on('currencies');

            $table->index(['tenant_id', 'type']);
        });

        // ── Bank Transactions (imported / manual) ─────────────────────────────
        Schema::create('bank_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bank_account_id');
            $table->date('transaction_date');
            $table->decimal('amount', 18, 4);
            $table->text('description')->nullable();
            $table->string('reference', 100)->nullable();
            $table->enum('type', ['debit', 'credit']);
            $table->enum('status', ['pending', 'matched', 'categorized', 'excluded'])->default('pending');
            $table->unsignedBigInteger('journal_entry_id')->nullable(); // matched entry
            $table->unsignedBigInteger('category_rule_id')->nullable();
            $table->enum('source', ['import', 'manual', 'api'])->default('manual');
            $table->json('raw_data')->nullable();
            $table->timestamp('imported_at')->useCurrent();
            $table->timestamps();

            $table->foreign('bank_account_id')->references('id')->on('bank_accounts')->cascadeOnDelete();
            $table->foreign('journal_entry_id')->references('id')->on('journal_entries')->nullOnDelete();

            $table->index(['bank_account_id', 'status']);
            $table->index(['bank_account_id', 'transaction_date']);
        });

        // ── Tax Codes ────────────────────────────────────────────────────────
        Schema::create('tax_codes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('code', 30);
            $table->string('name', 100);
            $table->decimal('rate', 8, 4);         // e.g. 10.0000 for 10%
            $table->enum('type', ['sales', 'purchase', 'both'])->default('both');
            $table->unsignedBigInteger('account_id'); // tax payable/receivable GL account
            $table->boolean('is_compound')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('account_id')->references('id')->on('chart_of_accounts');

            $table->unique(['tenant_id', 'code']);
        });

        // ── Cost Centers ────────────────────────────────────────────────────
        Schema::create('cost_centers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('code', 50);
            $table->string('name');
            $table->string('type', 100)->nullable(); // department, project, region
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('parent_id')->references('id')->on('cost_centers')->nullOnDelete();

            $table->unique(['tenant_id', 'code']);
        });

        // ── Payment Terms ────────────────────────────────────────────────────
        Schema::create('payment_terms', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('name', 100);           // e.g. "Net 30", "2/10 Net 30"
            $table->integer('days_due');
            $table->integer('discount_days')->nullable();
            $table->decimal('discount_percent', 5, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_terms');
        Schema::dropIfExists('cost_centers');
        Schema::dropIfExists('tax_codes');
        Schema::dropIfExists('bank_transactions');
        Schema::dropIfExists('bank_accounts');
    }
};
