<?php

// use Illuminate\Database\Migrations\Migration;
// use Illuminate\Database\Schema\Blueprint;
// use Illuminate\Support\Facades\Schema;

// return new class extends Migration
// {
//     public function up(): void
//     {
//         // Customers AR account
//         Schema::table('customers', function (Blueprint $table) {
//             $table->foreign('ar_account_id')->references('id')->on('accounts')->nullOnDelete();
//         });

//         // Suppliers AP account
//         Schema::table('suppliers', function (Blueprint $table) {
//             $table->foreign('ap_account_id')->references('id')->on('accounts')->nullOnDelete();
//         });

//         // Products account references
//         Schema::table('products', function (Blueprint $table) {
//             $table->foreign('income_account_id')->references('id')->on('accounts')->nullOnDelete();
//             $table->foreign('cogs_account_id')->references('id')->on('accounts')->nullOnDelete();
//             $table->foreign('inventory_account_id')->references('id')->on('accounts')->nullOnDelete();
//             $table->foreign('expense_account_id')->references('id')->on('accounts')->nullOnDelete();
//         });

//         // Purchase order lines account
//         Schema::table('purchase_order_lines', function (Blueprint $table) {
//             $table->foreign('account_id')->references('id')->on('accounts')->nullOnDelete();
//         });

//         // Purchase invoices AP account & JE
//         Schema::table('purchase_invoices', function (Blueprint $table) {
//             $table->foreign('ap_account_id')->references('id')->on('accounts')->nullOnDelete();
//             $table->foreign('journal_entry_id')->references('id')->on('journal_entries')->nullOnDelete();
//         });

//         // Purchase invoice lines account
//         Schema::table('purchase_invoice_lines', function (Blueprint $table) {
//             $table->foreign('account_id')->references('id')->on('accounts')->nullOnDelete();
//         });

//         // Purchase returns JE
//         Schema::table('purchase_returns', function (Blueprint $table) {
//             $table->foreign('journal_entry_id')->references('id')->on('journal_entries')->nullOnDelete();
//         });

//         // Sales order lines income account
//         Schema::table('sales_order_lines', function (Blueprint $table) {
//             $table->foreign('income_account_id')->references('id')->on('accounts')->nullOnDelete();
//         });

//         // Sales invoices AR account & JE
//         Schema::table('sales_invoices', function (Blueprint $table) {
//             $table->foreign('ar_account_id')->references('id')->on('accounts')->nullOnDelete();
//             $table->foreign('journal_entry_id')->references('id')->on('journal_entries')->nullOnDelete();
//         });

//         // Sales invoice lines income account
//         Schema::table('sales_invoice_lines', function (Blueprint $table) {
//             $table->foreign('income_account_id')->references('id')->on('accounts')->nullOnDelete();
//         });

//         // Sales returns JE
//         Schema::table('sales_returns', function (Blueprint $table) {
//             $table->foreign('journal_entry_id')->references('id')->on('journal_entries')->nullOnDelete();
//         });

//         // Tax rates account
//         Schema::table('tax_rates', function (Blueprint $table) {
//             $table->foreign('account_id')->references('id')->on('accounts')->nullOnDelete();
//         });

//         // Payments JE
//         Schema::table('payments', function (Blueprint $table) {
//             $table->foreign('journal_entry_id')->references('id')->on('journal_entries')->nullOnDelete();
//         });

//         // Credit memos JE
//         Schema::table('credit_memos', function (Blueprint $table) {
//             $table->foreign('journal_entry_id')->references('id')->on('journal_entries')->nullOnDelete();
//         });

//         // Bank transactions matched JE
//         Schema::table('bank_transactions', function (Blueprint $table) {
//             $table->foreign('matched_journal_entry_id')->references('id')->on('journal_entries')->nullOnDelete();
//             $table->foreign('category_rule_id')->references('id')->on('bank_category_rules')->nullOnDelete();
//         });

//         // Org units manager reference
//         Schema::table('org_units', function (Blueprint $table) {
//             $table->foreign('manager_user_id')->references('id')->on('users')->nullOnDelete();
//         });
//     }

//     public function down(): void
//     {
//         // Drop foreign keys in reverse order
//     }
// };