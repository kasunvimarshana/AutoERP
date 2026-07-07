<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\VehicleRental\Enums\RentalAgreementKind;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_deposit_requirements', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants', indexName: 'rental_deposit_requirements_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('agreement_id');
            $table->enum('agreement_kind', [RentalAgreementKind::CustomerRental->value])
                ->default(RentalAgreementKind::CustomerRental->value);
            $table->foreignId('customer_id');
            $table->decimal('required_amount', 20, 6);
            $table->foreignId('currency_id')->constrained('currencies', indexName: 'rental_deposit_requirements_currency_fk')->restrictOnDelete();
            $table->date('due_date')->nullable();
            $table->boolean('is_refundable')->default(true);
            $table->decimal('received_amount', 20, 6)->default('0.000000');
            $table->decimal('applied_amount', 20, 6)->default('0.000000');
            $table->decimal('refunded_amount', 20, 6)->default('0.000000');
            $table->decimal('forfeited_amount', 20, 6)->default('0.000000');
            $table->decimal('balance_amount', 20, 6)->default('0.000000');
            $table->string('status', 30)->default('pending');
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique('agreement_id', 'rental_deposit_requirements_agreement_uk');
            $table->index(['status', 'due_date'], 'rental_deposit_requirements_status_due_ix');

            $table->unique(['id', 'tenant_id'], 'rental_deposit_requirements_id_tenant_uk');
            $table->foreign(['customer_id', 'tenant_id'], 'rental_deposit_requirements_customer_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('customers')
                ->restrictOnDelete();
            $table->foreign(['organization_unit_id', 'tenant_id'], 'rental_deposit_requirements_organization_unit_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['agreement_id', 'tenant_id', 'agreement_kind', 'customer_id'], 'rental_deposit_req_agreement_kind_customer_fk')
                ->references(['id', 'tenant_id', 'agreement_kind', 'customer_id'])
                ->on('rental_agreements')
                ->cascadeOnDelete();

            $table->foreign(['created_by', 'tenant_id'], 'rental_deposit_requirements_created_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
            $table->foreign(['updated_by', 'tenant_id'], 'rental_deposit_requirements_updated_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_deposit_requirements');
    }
};
