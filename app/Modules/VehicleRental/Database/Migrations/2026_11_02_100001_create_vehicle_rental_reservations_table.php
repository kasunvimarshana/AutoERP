<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_rental_reservations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->foreignId('organization_unit_id');
            $table->string('reservation_number', 100);
            $table->foreignId('customer_id');
            $table->string('customer_name_snapshot');
            $table->foreignId('requested_vehicle_id')->nullable();
            $table->foreignId('requested_vehicle_category_id')->nullable();
            $table->string('rental_mode', 30);
            $table->string('billing_cycle', 30);
            $table->dateTime('requested_start_at');
            $table->dateTime('requested_end_at');
            $table->foreignId('currency_id')->constrained('currencies')->restrictOnDelete();
            $table->string('currency_code_snapshot', 10);
            $table->decimal('estimated_amount', 20, 6)->default('0.000000');
            $table->decimal('estimated_deposit_amount', 20, 6)->default('0.000000');
            $table->string('status', 30)->default('draft');
            $table->string('source', 30)->nullable();
            $table->text('remarks')->nullable();
            $table->foreignId('converted_agreement_id')->nullable();
            $table->unsignedBigInteger('confirmed_by')->nullable();
            $table->dateTime('confirmed_at')->nullable();
            $table->unsignedBigInteger('cancelled_by')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'reservation_number'], 'vr_reservations_tenant_number_uk');
            $table->unique(['id', 'tenant_id'], 'vr_reservations_id_tenant_uk');
            $table->index(['tenant_id', 'organization_unit_id', 'status'], 'vr_reservations_scope_status_ix');
            $table->index(['customer_id', 'requested_start_at'], 'vr_reservations_customer_start_ix');
            $table->index(['requested_vehicle_id', 'requested_start_at', 'requested_end_at'], 'vr_reservations_vehicle_period_ix');

            $table->foreign(['organization_unit_id', 'tenant_id'], 'vr_reservations_org_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['customer_id', 'tenant_id'], 'vr_reservations_customer_fk')
                ->references(['id', 'tenant_id'])
                ->on('customers')
                ->restrictOnDelete();
            $table->foreign(['requested_vehicle_id', 'tenant_id'], 'vr_reservations_vehicle_fk')
                ->references(['id', 'tenant_id'])
                ->on('vehicles')
                ->restrictOnDelete();
            $table->foreign(['requested_vehicle_category_id', 'tenant_id'], 'vr_reservations_category_fk')
                ->references(['id', 'tenant_id'])
                ->on('vehicle_categories')
                ->restrictOnDelete();
            $table->foreign(['converted_agreement_id', 'tenant_id'], 'vr_reservations_agreement_fk')
                ->references(['id', 'tenant_id'])
                ->on('vehicle_rental_customer_agreements')
                ->restrictOnDelete();

            $table->foreign(['confirmed_by', 'tenant_id'], 'vr_reservations_confirmed_by_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
            $table->foreign(['cancelled_by', 'tenant_id'], 'vr_reservations_cancelled_by_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
            $table->foreign(['created_by', 'tenant_id'], 'vr_reservations_created_by_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
            $table->foreign(['updated_by', 'tenant_id'], 'vr_reservations_updated_by_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_rental_reservations');
    }
};
