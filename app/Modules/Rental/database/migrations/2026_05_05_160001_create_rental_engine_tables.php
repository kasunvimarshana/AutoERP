<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_bookings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->string('booking_number');
            $table->foreignId('customer_id')->constrained('customers', 'id', 'rental_bookings_customer_id_fk')->cascadeOnDelete();
            $table->enum('rental_mode', ['with_driver', 'without_driver'])->default('without_driver');
            $table->enum('ownership_model', ['owned_fleet', 'third_party', 'mixed'])->default('owned_fleet');
            $table->enum('status', ['draft', 'reserved', 'active', 'completed', 'cancelled', 'no_show'])->default('draft');
            $table->timestamp('pickup_at');
            $table->timestamp('return_due_at');
            $table->timestamp('actual_return_at')->nullable();
            $table->string('pickup_location')->nullable();
            $table->string('return_location')->nullable();
            $table->foreignId('currency_id')->constrained('currencies', 'id', 'rental_bookings_currency_id_fk');
            $table->enum('rate_plan', ['hourly', 'daily', 'weekly', 'monthly', 'custom'])->default('daily');
            $table->decimal('rate_amount', 20, 6)->default(0);
            $table->decimal('estimated_amount', 20, 6)->default(0);
            $table->decimal('final_amount', 20, 6)->default(0);
            $table->decimal('security_deposit_amount', 20, 6)->default(0);
            $table->enum('security_deposit_status', ['not_required', 'held', 'partially_refunded', 'refunded', 'forfeited'])->default('not_required');
            $table->foreignId('partner_supplier_id')->nullable()->constrained('suppliers', 'id', 'rental_bookings_partner_supplier_id_fk')->nullOnDelete();
            $table->text('terms_and_conditions')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'org_unit_id', 'booking_number'], 'rental_bookings_tenant_number_uk');
            $table->index(['tenant_id', 'status', 'pickup_at'], 'rental_bookings_tenant_status_pickup_idx');
            $table->index(['tenant_id', 'customer_id', 'pickup_at'], 'rental_bookings_tenant_customer_pickup_idx');
        });

        Schema::create('rental_booking_assets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('rental_booking_id')->constrained('rental_bookings', 'id', 'rental_booking_assets_booking_id_fk')->cascadeOnDelete();
            $table->foreignId('asset_id')->constrained('assets', 'id', 'rental_booking_assets_asset_id_fk')->cascadeOnDelete();
            $table->decimal('meter_out', 20, 6)->nullable();
            $table->decimal('meter_in', 20, 6)->nullable();
            $table->string('meter_unit', 12)->default('km');
            $table->enum('asset_status', ['reserved', 'released', 'in_use', 'returned', 'cancelled'])->default('reserved');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'org_unit_id', 'rental_booking_id', 'asset_id'], 'rental_booking_assets_booking_asset_uk');
            $table->index(['tenant_id', 'asset_id', 'asset_status'], 'rental_booking_assets_tenant_asset_status_idx');
        });

        Schema::create('rental_driver_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('rental_booking_id')->constrained('rental_bookings', 'id', 'rental_driver_assignments_booking_id_fk')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees', 'id', 'rental_driver_assignments_employee_id_fk')->cascadeOnDelete();
            $table->foreignId('substitute_for_assignment_id')->nullable()->constrained('rental_driver_assignments', 'id', 'rental_driver_assignments_substitute_for_fk')->nullOnDelete();
            $table->enum('assignment_status', ['assigned', 'replaced', 'cancelled', 'completed'])->default('assigned');
            $table->timestamp('assigned_from')->nullable();
            $table->timestamp('assigned_to')->nullable();
            $table->text('substitution_reason')->nullable();
            $table->foreignId('assigned_by')->nullable()->constrained('users', 'id', 'rental_driver_assignments_assigned_by_fk')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'rental_booking_id', 'assignment_status'], 'rental_driver_assignments_booking_status_idx');
            $table->index(['tenant_id', 'employee_id', 'assigned_from'], 'rental_driver_assignments_employee_from_idx');
        });

        Schema::create('rental_inspections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('rental_booking_id')->constrained('rental_bookings', 'id', 'rental_inspections_booking_id_fk')->cascadeOnDelete();
            $table->foreignId('asset_id')->constrained('assets', 'id', 'rental_inspections_asset_id_fk')->cascadeOnDelete();
            $table->enum('inspection_type', ['pickup', 'return', 'incident_followup']);
            $table->enum('inspection_status', ['draft', 'submitted', 'approved'])->default('draft');
            $table->foreignId('inspected_by')->nullable()->constrained('users', 'id', 'rental_inspections_inspected_by_fk')->nullOnDelete();
            $table->timestamp('inspected_at')->nullable();
            $table->decimal('meter_reading', 20, 6)->nullable();
            $table->decimal('fuel_level_percent', 5, 2)->nullable();
            $table->text('damage_notes')->nullable();
            $table->json('media')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'rental_booking_id', 'inspection_type'], 'rental_inspections_booking_type_idx');
            $table->index(['tenant_id', 'asset_id', 'inspected_at'], 'rental_inspections_asset_date_idx');
        });

        Schema::create('rental_incidents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('rental_booking_id')->constrained('rental_bookings', 'id', 'rental_incidents_booking_id_fk')->cascadeOnDelete();
            $table->foreignId('asset_id')->constrained('assets', 'id', 'rental_incidents_asset_id_fk')->cascadeOnDelete();
            $table->enum('incident_type', ['damage', 'traffic_violation', 'late_return', 'other']);
            $table->enum('status', ['open', 'under_review', 'resolved', 'waived'])->default('open');
            $table->timestamp('occurred_at')->nullable();
            $table->foreignId('reported_by')->nullable()->constrained('users', 'id', 'rental_incidents_reported_by_fk')->nullOnDelete();
            $table->text('description')->nullable();
            $table->decimal('estimated_cost', 20, 6)->default(0);
            $table->decimal('recovered_amount', 20, 6)->default(0);
            $table->enum('recovery_status', ['none', 'partial', 'full'])->default('none');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'status', 'occurred_at'], 'rental_incidents_tenant_status_date_idx');
            $table->index(['tenant_id', 'asset_id', 'occurred_at'], 'rental_incidents_tenant_asset_date_idx');
        });

        Schema::create('rental_charges', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('rental_booking_id')->constrained('rental_bookings', 'id', 'rental_charges_booking_id_fk')->cascadeOnDelete();
            $table->foreignId('rental_incident_id')->nullable()->constrained('rental_incidents', 'id', 'rental_charges_incident_id_fk')->nullOnDelete();
            $table->enum('charge_type', ['rental_fee', 'penalty', 'extension', 'fuel', 'cleaning', 'other']);
            $table->enum('charge_direction', ['receivable', 'payable'])->default('receivable');
            $table->foreignId('currency_id')->constrained('currencies', 'id', 'rental_charges_currency_id_fk');
            $table->decimal('amount', 20, 6);
            $table->decimal('tax_amount', 20, 6)->default(0);
            $table->decimal('total_amount', 20, 6);
            $table->date('due_date')->nullable();
            $table->enum('status', ['draft', 'posted', 'paid', 'voided'])->default('draft');
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries', 'id', 'rental_charges_journal_entry_id_fk')->nullOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained('payments', 'id', 'rental_charges_payment_id_fk')->nullOnDelete();
            $table->foreignId('reversal_of_id')->nullable()->constrained('rental_charges', 'id', 'rental_charges_reversal_of_id_fk')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'rental_booking_id', 'status'], 'rental_charges_booking_status_idx');
            $table->index(['tenant_id', 'charge_type', 'status'], 'rental_charges_type_status_idx');
        });

        Schema::create('rental_deposits', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('rental_booking_id')->constrained('rental_bookings', 'id', 'rental_deposits_booking_id_fk')->cascadeOnDelete();
            $table->foreignId('currency_id')->constrained('currencies', 'id', 'rental_deposits_currency_id_fk');
            $table->decimal('held_amount', 20, 6);
            $table->decimal('released_amount', 20, 6)->default(0);
            $table->decimal('forfeited_amount', 20, 6)->default(0);
            $table->enum('status', ['held', 'released', 'partially_released', 'forfeited'])->default('held');
            $table->timestamp('held_at')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->foreignId('payment_id')->nullable()->constrained('payments', 'id', 'rental_deposits_payment_id_fk')->nullOnDelete();
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries', 'id', 'rental_deposits_journal_entry_id_fk')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'rental_booking_id', 'status'], 'rental_deposits_booking_status_idx');
        });

        Schema::create('rental_settlements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('rental_booking_id')->constrained('rental_bookings', 'id', 'rental_settlements_booking_id_fk')->cascadeOnDelete();
            $table->enum('settlement_party_type', ['driver', 'partner_supplier']);
            $table->foreignId('employee_id')->nullable()->constrained('employees', 'id', 'rental_settlements_employee_id_fk')->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers', 'id', 'rental_settlements_supplier_id_fk')->nullOnDelete();
            $table->enum('settlement_type', ['commission', 'payout', 'reimbursement', 'deduction']);
            $table->foreignId('currency_id')->constrained('currencies', 'id', 'rental_settlements_currency_id_fk');
            $table->decimal('amount', 20, 6);
            $table->decimal('tax_amount', 20, 6)->default(0);
            $table->decimal('total_amount', 20, 6);
            $table->enum('status', ['draft', 'approved', 'posted', 'paid', 'voided'])->default('draft');
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries', 'id', 'rental_settlements_journal_entry_id_fk')->nullOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained('payments', 'id', 'rental_settlements_payment_id_fk')->nullOnDelete();
            $table->foreignId('reversal_of_id')->nullable()->constrained('rental_settlements', 'id', 'rental_settlements_reversal_of_id_fk')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'rental_booking_id', 'status'], 'rental_settlements_booking_status_idx');
            $table->index(['tenant_id', 'settlement_party_type', 'status'], 'rental_settlements_party_status_idx');
        });

        Schema::create('rental_expenses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('rental_booking_id')->nullable()->constrained('rental_bookings', 'id', 'rental_expenses_booking_id_fk')->nullOnDelete();
            $table->foreignId('asset_id')->nullable()->constrained('assets', 'id', 'rental_expenses_asset_id_fk')->nullOnDelete();
            $table->enum('expense_type', ['fuel', 'toll', 'maintenance_pass_through', 'cleaning', 'other']);
            $table->timestamp('incurred_at')->nullable();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers', 'id', 'rental_expenses_supplier_id_fk')->nullOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained('employees', 'id', 'rental_expenses_employee_id_fk')->nullOnDelete();
            $table->foreignId('currency_id')->constrained('currencies', 'id', 'rental_expenses_currency_id_fk');
            $table->decimal('amount', 20, 6);
            $table->decimal('tax_amount', 20, 6)->default(0);
            $table->decimal('total_amount', 20, 6);
            $table->enum('status', ['draft', 'approved', 'posted', 'reimbursed', 'voided'])->default('draft');
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries', 'id', 'rental_expenses_journal_entry_id_fk')->nullOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained('payments', 'id', 'rental_expenses_payment_id_fk')->nullOnDelete();
            $table->foreignId('reversal_of_id')->nullable()->constrained('rental_expenses', 'id', 'rental_expenses_reversal_of_id_fk')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'expense_type', 'incurred_at'], 'rental_expenses_type_date_idx');
            $table->index(['tenant_id', 'status', 'incurred_at'], 'rental_expenses_status_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_expenses');
        Schema::dropIfExists('rental_settlements');
        Schema::dropIfExists('rental_deposits');
        Schema::dropIfExists('rental_charges');
        Schema::dropIfExists('rental_incidents');
        Schema::dropIfExists('rental_inspections');
        Schema::dropIfExists('rental_driver_assignments');
        Schema::dropIfExists('rental_booking_assets');
        Schema::dropIfExists('rental_bookings');
    }
};
