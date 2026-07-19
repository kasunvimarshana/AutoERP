<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\VehicleRental\Enums\RentalAgreementStatus;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_rental_agreements', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants', indexName: 'vr_agreements_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->string('agreement_number', 100);
            $table->string('kind', 20);
            $table->foreignId('customer_id')->nullable();
            $table->foreignId('supplier_id')->nullable();
            $table->date('executed_at')->nullable();
            $table->date('starts_on');
            $table->date('ends_on')->nullable();
            $table->string('billing_basis', 20);
            $table->foreignId('currency_id')->constrained('currencies', indexName: 'vr_agreements_currency_fk')->restrictOnDelete();
            $table->foreignId('tax_group_id')->nullable();
            $table->decimal('included_km', 20, 6)->default('0.000000');
            $table->boolean('deposit_required')->default(false);
            $table->decimal('deposit_amount', 20, 6)->default('0.000000');
            $table->unsignedInteger('payment_terms_days')->default(0);
            $table->string('status', 20)->default(RentalAgreementStatus::Draft->value);
            $table->text('terms')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('activated_by')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->unsignedBigInteger('closed_by')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'agreement_number'], 'vr_agreements_tenant_number_uk');
            $table->unique(['id', 'tenant_id'], 'vr_agreements_id_tenant_uk');
            $table->index(['tenant_id', 'organization_unit_id', 'kind', 'status'], 'vr_agreements_scope_kind_status_ix');
            $table->index(['customer_id', 'status'], 'vr_agreements_customer_status_ix');
            $table->index(['supplier_id', 'status'], 'vr_agreements_supplier_status_ix');

            $table->foreign(['organization_unit_id', 'tenant_id'], 'vr_agreements_org_tenant_fk')
                ->references(['id', 'tenant_id'])->on('organization_units')->restrictOnDelete();
            $table->foreign(['customer_id', 'tenant_id'], 'vr_agreements_customer_tenant_fk')
                ->references(['id', 'tenant_id'])->on('customers')->restrictOnDelete();
            $table->foreign(['supplier_id', 'tenant_id'], 'vr_agreements_supplier_tenant_fk')
                ->references(['id', 'tenant_id'])->on('suppliers')->restrictOnDelete();
            $table->foreign(['tax_group_id', 'tenant_id'], 'vr_agreements_tax_group_tenant_fk')
                ->references(['id', 'tenant_id'])->on('tax_groups')->restrictOnDelete();
            $table->foreign(['created_by', 'tenant_id'], 'vr_agreements_created_by_tenant_fk')
                ->references(['id', 'tenant_id'])->on('users')->restrictOnDelete();
            $table->foreign(['activated_by', 'tenant_id'], 'vr_agreements_activated_by_tenant_fk')
                ->references(['id', 'tenant_id'])->on('users')->restrictOnDelete();
            $table->foreign(['closed_by', 'tenant_id'], 'vr_agreements_closed_by_tenant_fk')
                ->references(['id', 'tenant_id'])->on('users')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_rental_agreements');
    }
};
