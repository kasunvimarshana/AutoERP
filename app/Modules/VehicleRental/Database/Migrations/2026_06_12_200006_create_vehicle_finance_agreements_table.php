<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_finance_agreements', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants', indexName: 'vehicle_finance_agreements_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->json('metadata')->nullable();
            $table->string('agreement_number', 100);
            $table->foreignId('supplier_id');
            $table->foreignId('vehicle_id');
            $table->date('agreement_date');
            $table->dateTime('starts_at');
            $table->dateTime('matures_at');
            $table->foreignId('currency_id')->constrained('currencies', indexName: 'vehicle_finance_agreements_currency_fk')->restrictOnDelete();
            $table->decimal('principal_amount', 20, 6)->default('0.000000');
            $table->decimal('initial_deposit_amount', 20, 6)->default('0.000000');
            $table->decimal('residual_value', 20, 6)->default('0.000000');
            $table->string('interest_method', 30)->default('flat');
            $table->decimal('annual_interest_rate', 12, 6)->default('0.000000');
            $table->string('installment_frequency', 30)->default('monthly');
            $table->unsignedInteger('installment_count');
            $table->unsignedSmallInteger('payment_term_days')->default(0);
            $table->foreignId('tax_group_id')->nullable();
            $table->string('status', 30)->default('draft');
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'agreement_number'], 'vehicle_finance_agreements_tenant_number_uk');
            $table->index(['vehicle_id', 'status', 'starts_at', 'matures_at'], 'vehicle_finance_agreements_vehicle_period_ix');
            $table->index(['supplier_id', 'status'], 'vehicle_finance_agreements_supplier_status_ix');

            $table->unique(['id', 'tenant_id'], 'vehicle_finance_agreements_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'vehicle_finance_agreements_organization_unit_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['supplier_id', 'tenant_id'], 'vehicle_finance_agreements_supplier_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('suppliers')
                ->restrictOnDelete();
            $table->foreign(['vehicle_id', 'tenant_id'], 'vehicle_finance_agreements_vehicle_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('vehicles')
                ->restrictOnDelete();
            $table->foreign(['tax_group_id', 'tenant_id'], 'vehicle_finance_agreements_tax_group_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('tax_groups')
                ->restrictOnDelete();

            $table->foreign(['approved_by', 'tenant_id'], 'vehicle_finance_agreements_approved_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
            $table->foreign(['created_by', 'tenant_id'], 'vehicle_finance_agreements_created_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
            $table->foreign(['updated_by', 'tenant_id'], 'vehicle_finance_agreements_updated_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_finance_agreements');
    }
};
