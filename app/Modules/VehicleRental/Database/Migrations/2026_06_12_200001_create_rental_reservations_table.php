<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_reservations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->json('metadata')->nullable();
            $table->string('reservation_number', 100);
            $table->foreignId('customer_id');
            $table->foreignId('requested_vehicle_id')->nullable();
            $table->foreignId('requested_vehicle_category_id')->nullable();
            $table->string('rental_mode', 30);
            $table->string('billing_cycle', 30);
            $table->dateTime('requested_start_at');
            $table->dateTime('requested_end_at');
            $table->foreignId('currency_id')->constrained('currencies')->restrictOnDelete();
            $table->decimal('estimated_amount', 20, 6)->default('0.000000');
            $table->decimal('estimated_deposit_amount', 20, 6)->default('0.000000');
            $table->string('status', 30)->default('draft');
            $table->string('source', 30)->nullable();
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('confirmed_by')->nullable();
            $table->dateTime('confirmed_at')->nullable();
            $table->unsignedBigInteger('cancelled_by')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'reservation_number'], 'rental_reservations_tenant_number_uk');
            $table->index(['tenant_id', 'organization_unit_id', 'status'], 'rental_reservations_scope_status_idx');
            $table->index(['customer_id', 'requested_start_at'], 'rental_reservations_customer_start_idx');
            $table->index(['requested_vehicle_id', 'requested_start_at', 'requested_end_at'], 'rental_reservations_vehicle_period_idx');

            $table->unique(['id', 'tenant_id'], 'rental_reservations_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'rental_reservations_organization_unit_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['customer_id', 'tenant_id'], 'rental_reservations_customer_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('customers')
                ->restrictOnDelete();
            $table->foreign(['requested_vehicle_id', 'tenant_id'], 'rental_reservations_requested_vehicle_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('vehicles')
                ->restrictOnDelete();
            $table->foreign(['requested_vehicle_category_id', 'tenant_id'], 'rental_reservations_requested_vehicle_category_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('vehicle_categories')
                ->restrictOnDelete();

            $table->foreign(['cancelled_by', 'tenant_id'], 'rental_reservations_cancelled_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
            $table->foreign(['created_by', 'tenant_id'], 'rental_reservations_created_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
            $table->foreign(['updated_by', 'tenant_id'], 'rental_reservations_updated_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_reservations');
    }
};
