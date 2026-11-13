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
        Schema::create('vehicle_rental_agreement_adjustments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->foreignId('organization_unit_id');
            $table->foreignId('customer_agreement_id')->nullable();
            $table->foreignId('owner_agreement_id')->nullable();
            $table->foreignId('invoice_id')->nullable();
            $table->string('adjustment_type', 32);
            $table->string('category', 64);
            $table->string('status', 32)->default('draft');
            $table->decimal('amount', AgreementFields::DECIMAL_PRECISION, AgreementFields::DECIMAL_SCALE);
            $table->decimal('original_amount', AgreementFields::DECIMAL_PRECISION, AgreementFields::DECIMAL_SCALE);
            $table->decimal('tax_amount', AgreementFields::DECIMAL_PRECISION, AgreementFields::DECIMAL_SCALE)->default(0);
            $table->text('reason');
            $table->foreignId('authorized_by_user_id')->nullable();
            $table->dateTime('authorized_at')->nullable();
            $table->foreignId('actor_id');
            $table->unsignedBigInteger('row_version')->default(AgreementFields::INITIAL_VERSION);
            $table->timestamps();

            $table->unique(['id', 'tenant_id'], 'vraa_tenant_uk');
            $table->index(['tenant_id', 'organization_unit_id', 'status'], 'vraa_context_ix');
            $table->index(['customer_agreement_id', 'tenant_id'], 'vraa_customer_ix');
            $table->index(['owner_agreement_id', 'tenant_id'], 'vraa_owner_ix');
            $table->index(['invoice_id', 'tenant_id'], 'vraa_invoice_ix');

            $table->foreign(['organization_unit_id', 'tenant_id'], 'vraa_org_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['customer_agreement_id', 'tenant_id'], 'vraa_customer_fk')
                ->references(['id', 'tenant_id'])
                ->on('vehicle_rental_customer_agreements')
                ->restrictOnDelete();
            $table->foreign(['owner_agreement_id', 'tenant_id'], 'vraa_owner_fk')
                ->references(['id', 'tenant_id'])
                ->on('vehicle_rental_owner_agreements')
                ->restrictOnDelete();
            $table->foreign(['invoice_id', 'tenant_id'], 'vraa_invoice_fk')
                ->references(['id', 'tenant_id'])
                ->on('invoices')
                ->restrictOnDelete();
            $table->foreign(['actor_id', 'tenant_id'], 'vraa_actor_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
            $table->foreign(['authorized_by_user_id', 'tenant_id'], 'vraa_auth_user_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_rental_agreement_adjustments');
    }
};
