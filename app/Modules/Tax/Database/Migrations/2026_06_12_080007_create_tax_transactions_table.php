<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_transactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', indexName: 'tax_transactions_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->foreignId('tax_id')->nullable();
            $table->foreignId('tax_document_snapshot_id')->nullable();
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
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'organization_unit_id', 'transaction_date'], 'tax_transactions_scope_date_ix');
            $table->index(['tax_type', 'tax_code', 'transaction_date'], 'tax_transactions_tax_date_ix');
            $table->index(['party_type', 'party_id'], 'tax_transactions_party_ix');
            $table->index(['source_type', 'source_id'], 'tax_transactions_source_ix');

            $table->unique(['id', 'tenant_id'], 'tax_transactions_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'tax_transactions_organization_unit_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['tax_id', 'tenant_id'], 'tax_transactions_tax_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('taxes')
                ->restrictOnDelete();
            $table->foreign(['tax_document_snapshot_id', 'tenant_id'], 'tax_transactions_tax_document_snapshot_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('tax_document_snapshots')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_transactions');
    }
};
