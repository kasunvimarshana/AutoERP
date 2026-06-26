<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_bank_accounts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', indexName: 'supplier_bank_accounts_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->foreignId('supplier_id');
            $table->string('bank_name');
            $table->string('branch_name')->nullable();
            $table->string('account_name');
            $table->string('account_number');
            $table->string('swift_code')->nullable();
            $table->string('iban')->nullable();
            $table->foreignId('currency_id')->nullable()->constrained('currencies', indexName: 'supplier_bank_accounts_currency_fk')->nullOnDelete();
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'organization_unit_id'], 'supplier_bank_accounts_tenant_org_ix');
            $table->index('supplier_id', 'supplier_bank_accounts_supplier_ix');
            $table->index('account_number', 'supplier_bank_accounts_number_ix');
            $table->unique(['supplier_id', 'account_number'], 'supplier_bank_accounts_supplier_number_uk');

            $table->unique(['id', 'tenant_id'], 'supplier_bank_accounts_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'supplier_bank_accounts_organization_unit_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['supplier_id', 'tenant_id'], 'supplier_bank_accounts_supplier_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('suppliers')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_bank_accounts');
    }
};
