<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('parent_id')->nullable()->constrained('accounts', 'id', 'accounts_parent_id_fk')->nullOnDelete();
            $table->string('code');
            $table->string('name');
            $table->enum('type', ['asset', 'liability', 'equity', 'revenue', 'expense']);
            $table->string('sub_type')->nullable(); // current_asset, accounts_receivable, etc.
            $table->enum('normal_balance', ['debit', 'credit']);
            $table->boolean('is_system')->default(false);
            $table->boolean('is_bank_account')->default(false);
            $table->boolean('is_credit_card')->default(false);
            $table->foreignId('currency_id')->nullable()->constrained('currencies', 'id', 'accounts_currency_id_fk')->nullOnDelete();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('path')->nullable(); // materialized path for hierarchy
            $table->unsignedInteger('depth')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'code'], 'accounts_tenant_code_uk');
            $table->index(['tenant_id', 'type'], 'accounts_tenant_type_idx');
            $table->index(['tenant_id', 'parent_id'], 'accounts_tenant_parent_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
