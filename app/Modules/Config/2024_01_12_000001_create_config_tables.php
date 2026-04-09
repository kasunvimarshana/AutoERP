<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Configuration Module
     *
     * settings: key-value store per tenant per module.
     *   Enables full per-tenant customization without schema changes.
     *
     * numbering_sequences: document number auto-generation.
     *   Supports prefix/suffix, zero-padding, and periodic reset.
     *   e.g. INV-2024-000001, GRN/2024/01/00042, SO-00001
     *
     * bank_categorization_rules: intelligent auto-categorization of
     *   bank transactions based on configurable pattern matching.
     */
    public function up(): void
    {
        // ── Settings ──────────────────────────────────────────────────────────
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('module', 100);     // e.g. inventory, finance, sales
            $table->string('key', 100);        // e.g. default_warehouse_id, base_currency
            $table->text('value')->nullable();
            $table->enum('type', ['string', 'integer', 'boolean', 'json', 'date'])->default('string');
            $table->boolean('is_encrypted')->default(false);
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->unique(['tenant_id', 'module', 'key']);
        });

        // ── Numbering Sequences ────────────────────────────────────────────────
        Schema::create('numbering_sequences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('document_type', 100);      // purchase_order, sales_order, invoice, grn…
            $table->string('prefix', 20)->nullable();  // e.g. "INV-", "GRN/"
            $table->string('suffix', 20)->nullable();
            $table->unsignedBigInteger('current_number')->default(0);
            $table->unsignedTinyInteger('padding_length')->default(6);  // zero-padded length
            $table->enum('reset_frequency', ['never', 'yearly', 'monthly', 'daily'])->default('never');
            $table->timestamp('last_reset_at')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->unique(['tenant_id', 'document_type']);
        });

        // ── Bank Categorization Rules (for auto-categorization engine) ─────────
        Schema::create('bank_categorization_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('name', 100);
            $table->string('match_field', 50);          // description, reference, amount
            $table->string('match_operator', 20);        // contains, starts_with, equals, regex
            $table->string('match_value', 255);
            $table->unsignedBigInteger('account_id');    // target GL account
            $table->unsignedBigInteger('party_id')->nullable(); // auto-assign party
            $table->integer('priority')->default(0);     // lower = higher priority
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('account_id')->references('id')->on('chart_of_accounts');
            $table->foreign('party_id')->references('id')->on('parties')->nullOnDelete();

            $table->index(['tenant_id', 'is_active', 'priority']);
        });

        // ── Notification Preferences ─────────────────────────────────────────
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('user_id');
            $table->string('event_type', 100);       // low_stock, overdue_invoice, etc.
            $table->boolean('in_app')->default(true);
            $table->boolean('email')->default(false);
            $table->boolean('sms')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->unique(['tenant_id', 'user_id', 'event_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
        Schema::dropIfExists('bank_categorization_rules');
        Schema::dropIfExists('numbering_sequences');
        Schema::dropIfExists('settings');
    }
};
