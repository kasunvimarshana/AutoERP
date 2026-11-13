<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\VehicleRental\Constants\AgreementFields;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_rental_billing_cycles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->foreignId('organization_unit_id');
            $table->string('cycle_number', 64);
            $table->foreignId('customer_agreement_id')->nullable();
            $table->foreignId('owner_agreement_id')->nullable();
            $table->foreignId('customer_base_charge_id')->nullable();
            $table->foreignId('owner_base_charge_id')->nullable();
            $table->date('period_from');
            $table->date('period_until');
            $table->string('status', 32)->default('draft');
            $table->string('billing_mode', 32)->default('without_running_chart');
            $table->string('finalization_mode', 32)->default('supplementary_invoice');
            $table->foreignId('initial_invoice_id')->nullable();
            $table->foreignId('final_invoice_id')->nullable();
            $table->foreignId('supplementary_invoice_id')->nullable();
            $table->decimal('base_rent_amount', AgreementFields::DECIMAL_PRECISION, AgreementFields::DECIMAL_SCALE)->default(0);
            $table->decimal('excess_km_amount', AgreementFields::DECIMAL_PRECISION, AgreementFields::DECIMAL_SCALE)->default(0);
            $table->decimal('overtime_amount', AgreementFields::DECIMAL_PRECISION, AgreementFields::DECIMAL_SCALE)->default(0);
            $table->decimal('night_out_amount', AgreementFields::DECIMAL_PRECISION, AgreementFields::DECIMAL_SCALE)->default(0);
            $table->decimal('tolls_amount', AgreementFields::DECIMAL_PRECISION, AgreementFields::DECIMAL_SCALE)->default(0);
            $table->decimal('additions_amount', AgreementFields::DECIMAL_PRECISION, AgreementFields::DECIMAL_SCALE)->default(0);
            $table->decimal('deductions_amount', AgreementFields::DECIMAL_PRECISION, AgreementFields::DECIMAL_SCALE)->default(0);
            $table->decimal('net_subtotal', AgreementFields::DECIMAL_PRECISION, AgreementFields::DECIMAL_SCALE)->default(0);
            $table->decimal('tax_amount', AgreementFields::DECIMAL_PRECISION, AgreementFields::DECIMAL_SCALE)->default(0);
            $table->decimal('total_billed_amount', AgreementFields::DECIMAL_PRECISION, AgreementFields::DECIMAL_SCALE)->default(0);
            $table->decimal('total_paid_amount', AgreementFields::DECIMAL_PRECISION, AgreementFields::DECIMAL_SCALE)->default(0);
            $table->foreignId('currency_id')->constrained('currencies')->restrictOnDelete();
            $table->decimal('exchange_rate', AgreementFields::DECIMAL_PRECISION, AgreementFields::DECIMAL_SCALE)->default(1);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('finalized_by')->nullable();
            $table->dateTime('finalized_at')->nullable();
            $table->unsignedBigInteger('row_version')->default(AgreementFields::INITIAL_VERSION);
            $table->timestamps();

            $table->unique(['id', 'tenant_id'], 'vrbc_tenant_uk');
            $table->unique(['tenant_id', 'cycle_number'], 'vrbc_cycle_number_uk');
            $table->index(['tenant_id', 'organization_unit_id', 'status'], 'vrbc_context_ix');
            $table->index(['customer_agreement_id', 'period_from', 'period_until'], 'vrbc_customer_period_ix');
            $table->index(['owner_agreement_id', 'period_from', 'period_until'], 'vrbc_owner_period_ix');

            $table->foreign(['organization_unit_id', 'tenant_id'], 'vrbc_org_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['customer_agreement_id', 'tenant_id'], 'vrbc_customer_fk')
                ->references(['id', 'tenant_id'])
                ->on('vehicle_rental_customer_agreements')
                ->restrictOnDelete();
            $table->foreign(['owner_agreement_id', 'tenant_id'], 'vrbc_owner_fk')
                ->references(['id', 'tenant_id'])
                ->on('vehicle_rental_owner_agreements')
                ->restrictOnDelete();
            $table->foreign(['customer_base_charge_id', 'tenant_id'], 'vrbc_cust_charge_fk')
                ->references(['id', 'tenant_id'])
                ->on('vehicle_rental_customer_base_charges')
                ->restrictOnDelete();
            $table->foreign(['owner_base_charge_id', 'tenant_id'], 'vrbc_owner_charge_fk')
                ->references(['id', 'tenant_id'])
                ->on('vehicle_rental_owner_base_charges')
                ->restrictOnDelete();
            $table->foreign(['initial_invoice_id', 'tenant_id'], 'vrbc_init_inv_fk')
                ->references(['id', 'tenant_id'])
                ->on('invoices')
                ->restrictOnDelete();
            $table->foreign(['final_invoice_id', 'tenant_id'], 'vrbc_final_inv_fk')
                ->references(['id', 'tenant_id'])
                ->on('invoices')
                ->restrictOnDelete();
            $table->foreign(['supplementary_invoice_id', 'tenant_id'], 'vrbc_supp_inv_fk')
                ->references(['id', 'tenant_id'])
                ->on('invoices')
                ->restrictOnDelete();
            $table->foreign(['created_by', 'tenant_id'], 'vrbc_created_by_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
            $table->foreign(['finalized_by', 'tenant_id'], 'vrbc_finalized_by_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_rental_billing_cycles');
    }
};
