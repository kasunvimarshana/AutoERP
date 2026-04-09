<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Chart of Accounts — hierarchical, GAAP/IFRS compliant.
     * Supports asset, liability, equity, income, expense, contra accounts.
     * normal_balance determines default debit/credit side per account type.
     */
    public function up(): void
    {
        Schema::create('chart_of_accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('parent_id')->nullable();  // hierarchical
            $table->string('code', 30);
            $table->string('name');
            $table->enum('type', ['asset', 'liability', 'equity', 'income', 'expense', 'contra']);
            $table->string('sub_type', 100)->nullable(); // current_asset, fixed_asset, ap, ar, etc.
            $table->enum('normal_balance', ['debit', 'credit']); // debit: assets/expenses; credit: liabilities/equity/income
            $table->boolean('is_bank')->default(false);           // linked to bank account
            $table->boolean('is_control')->default(false);        // AP/AR control accounts
            $table->unsignedBigInteger('currency_id')->nullable(); // for foreign-currency accounts
            $table->unsignedTinyInteger('level')->default(1);
            $table->string('path', 500);                          // materialized path for tree queries
            $table->boolean('is_leaf')->default(true);            // only leaf accounts accept posting
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('parent_id')->references('id')->on('chart_of_accounts')->nullOnDelete();
            $table->foreign('currency_id')->references('id')->on('currencies')->nullOnDelete();

            $table->unique(['tenant_id', 'code']);
            $table->index(['tenant_id', 'type']);
            $table->index(['tenant_id', 'is_leaf', 'is_active']);
            $table->index('path');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chart_of_accounts');
    }
};
