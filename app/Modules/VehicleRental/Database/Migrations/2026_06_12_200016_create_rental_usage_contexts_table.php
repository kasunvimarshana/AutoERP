<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_usage_contexts', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants', indexName: 'rental_usage_contexts_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('usage_log_id');
            $table->string('financial_side', 20);
            $table->foreignId('agreement_id');
            $table->foreignId('vehicle_allocation_id');
            $table->foreignId('rate_version_id');
            $table->foreignId('customer_id')->nullable();
            $table->foreignId('supplier_id')->nullable();
            $table->foreignId('currency_id')->constrained('currencies', indexName: 'rental_usage_contexts_currency_fk')->restrictOnDelete();
            $table->char('context_fingerprint', 64);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['usage_log_id', 'financial_side', 'agreement_id'], 'rental_usage_contexts_log_side_agreement_uk');
            $table->unique(['tenant_id', 'context_fingerprint'], 'rental_usage_contexts_fingerprint_uk');
            $table->index(['agreement_id', 'financial_side', 'usage_log_id'], 'rental_usage_contexts_agreement_side_ix');

            $table->unique(['id', 'tenant_id'], 'rental_usage_contexts_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'rental_usage_contexts_organization_unit_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['usage_log_id', 'tenant_id'], 'rental_usage_contexts_usage_log_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('rental_usage_logs')
                ->cascadeOnDelete();
            $table->foreign(['agreement_id', 'tenant_id'], 'rental_usage_contexts_agreement_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('rental_agreements')
                ->restrictOnDelete();
            $table->foreign(['vehicle_allocation_id', 'tenant_id'], 'rental_usage_contexts_vehicle_allocation_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('rental_vehicle_allocations')
                ->restrictOnDelete();
            $table->foreign(['rate_version_id', 'tenant_id'], 'rental_usage_contexts_rate_version_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('rental_agreement_rate_versions')
                ->restrictOnDelete();
            $table->foreign(['customer_id', 'tenant_id'], 'rental_usage_contexts_customer_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('customers')
                ->restrictOnDelete();
            $table->foreign(['supplier_id', 'tenant_id'], 'rental_usage_contexts_supplier_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('suppliers')
                ->restrictOnDelete();

            $table->foreign(['created_by', 'tenant_id'], 'rental_usage_contexts_created_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
            $table->foreign(['updated_by', 'tenant_id'], 'rental_usage_contexts_updated_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_usage_contexts');
    }
};
