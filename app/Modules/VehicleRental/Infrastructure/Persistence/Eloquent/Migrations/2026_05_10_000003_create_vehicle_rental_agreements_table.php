<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_rental_agreements', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();

            $table->foreignId('parent_agreement_id')->nullable()->constrained('vehicle_rental_agreements')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('provider_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->foreignId('rental_vehicle_id')->constrained('vehicle_rental_vehicles')->restrictOnDelete();
            $table->foreignId('assigned_driver_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('price_list_id')->nullable()->constrained('price_lists')->nullOnDelete();
            $table->foreignId('currency_id')->nullable()->constrained('currencies')->nullOnDelete();
            $table->foreignId('payment_term_id')->nullable()->constrained('payment_terms')->nullOnDelete();
            $table->foreignId('header_tax_group_id')->nullable()->constrained('tax_groups')->nullOnDelete();

            $table->string('agreement_number');
            $table->uuid('rental_operation_uuid')->nullable()->comment('Groups linked lessee and lessor agreements for one rental operation');
            $table->string('agreement_role')->default('lessee')->comment('lessee, lessor');
            $table->foreignId('lessee_agreement_id')->nullable()->constrained('vehicle_rental_agreements')->nullOnDelete();
            $table->foreignId('lessor_agreement_id')->nullable()->constrained('vehicle_rental_agreements')->nullOnDelete();
            $table->string('lessor_party_type')->nullable()->comment('supplier, customer, company, external_party');
            $table->unsignedBigInteger('lessor_party_id')->nullable();
            $table->string('lessor_party_name')->nullable();
            $table->string('status')->default('draft');
            $table->string('invoice_status')->default('pending');
            $table->string('payment_status')->default('unpaid');
            $table->string('finance_status')->default('draft');
            $table->string('driver_mode')->default('without_driver')->comment('with_driver, without_driver');
            $table->string('rate_model');
            $table->string('billing_frequency')->nullable();
            $table->date('agreement_date');
            $table->dateTime('start_datetime');
            $table->dateTime('end_datetime')->nullable();
            $table->decimal('hour_limit', 20, 4)->default(0);
            $table->decimal('kilometer_limit', 20, 4)->default(0);
            $table->decimal('allowed_daily_hours', 20, 4)->default(0);
            $table->decimal('allowed_daily_km', 20, 4)->default(0);
            $table->decimal('advance_amount', 20, 4)->default(0);
            $table->decimal('deposit_amount', 20, 4)->default(0);
            $table->decimal('exchange_rate', 20, 6)->default(1);
            $table->text('terms_and_conditions')->nullable();
            $table->text('customer_notes')->nullable();
            $table->text('internal_notes')->nullable();
            $table->string('pickup_location')->nullable();
            $table->string('return_location')->nullable();
            $table->dateTime('actual_pickup_at')->nullable();
            $table->dateTime('actual_return_at')->nullable();

            $table->decimal('estimated_subtotal', 20, 4)->default(0);
            $table->decimal('estimated_discount_total', 20, 4)->default(0);
            $table->decimal('estimated_tax_total', 20, 4)->default(0);
            $table->decimal('estimated_grand_total', 20, 4)->default(0);
            $table->decimal('invoiced_total', 20, 4)->default(0);
            $table->decimal('paid_total', 20, 4)->default(0);
            $table->decimal('provider_payable_total', 20, 4)->default(0);
            $table->decimal('provider_paid_total', 20, 4)->default(0);
            $table->decimal('outstanding_balance', 20, 4)->default(0);

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('confirmed_by')->nullable();
            $table->unsignedBigInteger('started_by')->nullable();
            $table->unsignedBigInteger('completed_by')->nullable();
            $table->unsignedBigInteger('closed_by')->nullable();
            $table->unsignedBigInteger('cancelled_by')->nullable();
            $table->unsignedBigInteger('reversed_by')->nullable();
            $table->dateTime('confirmed_at')->nullable();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->dateTime('closed_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->dateTime('reversed_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'agreement_number'], 'vehicle_rental_agreements_number_uk');
            $table->index(['tenant_id', 'status'], 'vehicle_rental_agreements_status_idx');
            $table->index(['tenant_id', 'rental_vehicle_id'], 'vehicle_rental_agreements_vehicle_idx');
            $table->index(['tenant_id', 'customer_id'], 'vehicle_rental_agreements_customer_idx');
            $table->index(['tenant_id', 'provider_id'], 'vehicle_rental_agreements_provider_idx');
            $table->index(['tenant_id', 'rental_operation_uuid'], 'vehicle_rental_agreements_operation_idx');
            $table->index(['tenant_id', 'agreement_role'], 'vehicle_rental_agreements_role_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_rental_agreements');
    }
};
