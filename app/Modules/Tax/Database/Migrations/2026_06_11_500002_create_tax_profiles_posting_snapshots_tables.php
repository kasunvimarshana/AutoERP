<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_tax_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->foreignId('customer_id')->constrained('customers', 'id')->cascadeOnDelete();
            $table->foreignId('tax_group_id')->nullable()->constrained('tax_groups', 'id')->nullOnDelete();
            $table->string('registration_number')->nullable();
            $table->string('exemption_status', 50)->default('taxable');
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique('customer_id', 'customer_tax_profiles_customer_uk');
            $table->index(['tenant_id', 'organization_unit_id', 'active'], 'customer_tax_profiles_scope_active_idx');
        });

        Schema::create('supplier_tax_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers', 'id')->cascadeOnDelete();
            $table->foreignId('tax_group_id')->nullable()->constrained('tax_groups', 'id')->nullOnDelete();
            $table->string('registration_number')->nullable();
            $table->string('exemption_status', 50)->default('taxable');
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique('supplier_id', 'supplier_tax_profiles_supplier_uk');
            $table->index(['tenant_id', 'organization_unit_id', 'active'], 'supplier_tax_profiles_scope_active_idx');
        });

        Schema::create('tax_posting_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->foreignId('tax_id')->constrained('taxes', 'id')->cascadeOnDelete();
            $table->string('direction', 50)->default('tax');
            $table->foreignId('account_id')->constrained('finance_accounts', 'id')->restrictOnDelete();
            $table->string('posting_key', 100)->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['tenant_id', 'organization_unit_id', 'tax_id', 'direction'], 'tax_posting_profiles_scope_tax_dir_uk');
            $table->index(['tenant_id', 'organization_unit_id', 'active'], 'tax_posting_profiles_scope_active_idx');
        });

        Schema::create('tax_document_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->string('source_module', 100)->nullable();
            $table->string('source_type', 100);
            $table->unsignedBigInteger('source_id');
            $table->string('source_number', 150)->nullable();
            $table->date('source_date')->nullable();
            $table->string('line_type', 100)->nullable();
            $table->unsignedBigInteger('line_id')->nullable();
            $table->foreignId('tax_id')->nullable()->constrained('taxes', 'id')->nullOnDelete();
            $table->string('tax_code', 100);
            $table->string('tax_name');
            $table->string('tax_type', 100);
            $table->string('calculation_method', 30);
            $table->decimal('rate', 20, 6)->default('0.000000');
            $table->unsignedInteger('sequence')->default(1);
            $table->decimal('taxable_amount', 20, 6)->default('0.000000');
            $table->decimal('tax_amount', 20, 6)->default('0.000000');
            $table->decimal('total_amount', 20, 6)->default('0.000000');
            $table->boolean('is_withholding')->default(false);
            $table->boolean('recoverable')->default(false);
            $table->boolean('payable')->default(false);
            $table->boolean('receivable')->default(false);
            $table->boolean('posted')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'organization_unit_id', 'source_type', 'source_id'], 'tax_snapshots_source_idx');
            $table->index(['tax_code', 'source_date'], 'tax_snapshots_code_date_idx');
        });

        Schema::create('tax_transactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->foreignId('tax_id')->nullable()->constrained('taxes', 'id')->nullOnDelete();
            $table->foreignId('tax_document_snapshot_id')->nullable()->constrained('tax_document_snapshots', 'id')->nullOnDelete();
            $table->date('transaction_date');
            $table->string('source_module', 100)->nullable();
            $table->string('source_type', 100);
            $table->unsignedBigInteger('source_id');
            $table->string('source_number', 150)->nullable();
            $table->string('party_type', 50)->nullable();
            $table->unsignedBigInteger('party_id')->nullable();
            $table->string('tax_code', 100);
            $table->string('tax_name');
            $table->string('tax_type', 100);
            $table->decimal('taxable_amount', 20, 6)->default('0.000000');
            $table->decimal('tax_amount', 20, 6)->default('0.000000');
            $table->decimal('withholding_amount', 20, 6)->default('0.000000');
            $table->boolean('is_withholding')->default(false);
            $table->boolean('recoverable')->default(false);
            $table->boolean('payable')->default(false);
            $table->boolean('receivable')->default(false);
            $table->foreignId('account_id')->nullable()->constrained('finance_accounts', 'id')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'organization_unit_id', 'transaction_date'], 'tax_transactions_scope_date_idx');
            $table->index(['tax_type', 'tax_code', 'transaction_date'], 'tax_transactions_tax_date_idx');
            $table->index(['party_type', 'party_id'], 'tax_transactions_party_idx');
            $table->index(['source_type', 'source_id'], 'tax_transactions_source_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_transactions');
        Schema::dropIfExists('tax_document_snapshots');
        Schema::dropIfExists('tax_posting_profiles');
        Schema::dropIfExists('supplier_tax_profiles');
        Schema::dropIfExists('customer_tax_profiles');
    }
};
