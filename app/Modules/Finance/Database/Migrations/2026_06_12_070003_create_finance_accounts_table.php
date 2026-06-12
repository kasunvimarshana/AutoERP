<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->foreignId('account_type_id')->constrained('finance_account_types', 'id');
            $table->foreignId('account_category_id')->nullable()->constrained('finance_account_categories', 'id')->nullOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('finance_accounts', 'id')->nullOnDelete();
            $table->string('code', 100);
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('normal_balance', ['debit', 'credit']);
            $table->boolean('is_control_account')->default(false);
            $table->boolean('is_posting_account')->default(true);
            $table->boolean('is_cash_account')->default(false);
            $table->boolean('is_bank_account')->default(false);
            $table->boolean('is_tax_account')->default(false);
            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true);
            $table->decimal('opening_balance', 20, 6)->default('0');
            $table->decimal('current_balance', 20, 6)->default('0');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'code'], 'finance_accounts_tenant_code_uk');
            $table->index(['tenant_id', 'organization_unit_id'], 'finance_accounts_tenant_org_idx');
            $table->index('account_type_id', 'finance_accounts_type_idx');
            $table->index('parent_id', 'finance_accounts_parent_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_accounts');
    }
};
