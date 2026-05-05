<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_driver_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');

            $table->foreignId('rental_booking_id')->constrained('rental_bookings', 'id', 'rda_booking_id_fk')->cascadeOnDelete();
            $table->foreignId('asset_id')->nullable()->constrained('assets', 'id', 'rda_asset_id_fk')->nullOnDelete()
                ->comment('Null = assignment applies to the whole booking');
            $table->foreignId('employee_id')->constrained('employees', 'id', 'rda_employee_id_fk')->cascadeOnDelete();
            $table->enum('assignment_role', ['primary', 'substitute', 'relief'])->default('primary');
            $table->foreignId('substituted_assignment_id')->nullable()
                ->constrained('rental_driver_assignments', 'id', 'rda_substituted_id_fk')->nullOnDelete()
                ->comment('For substitution chain tracking');

            $table->enum('status', ['assigned', 'active', 'relieved', 'cancelled'])->default('assigned');
            $table->timestamp('assigned_from')->nullable();
            $table->timestamp('assigned_to')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'rental_booking_id', 'status'], 'rda_tenant_booking_status_idx');
            $table->index(['tenant_id', 'employee_id', 'status'], 'rda_tenant_employee_status_idx');
        });

        Schema::create('rental_inspections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');

            $table->foreignId('rental_booking_id')->constrained('rental_bookings', 'id', 'ri_booking_id_fk')->cascadeOnDelete();
            $table->foreignId('rental_booking_asset_id')->nullable()
                ->constrained('rental_booking_assets', 'id', 'ri_booking_asset_id_fk')->nullOnDelete();
            $table->foreignId('asset_id')->constrained('assets', 'id', 'ri_asset_id_fk')->cascadeOnDelete();

            $table->enum('inspection_type', ['pre_rental', 'post_rental', 'incident', 'handover'])->default('pre_rental');
            $table->enum('condition_status', ['excellent', 'good', 'fair', 'poor', 'damaged'])->default('good');
            $table->foreignId('inspected_by')->nullable()->constrained('users', 'id', 'ri_inspected_by_fk')->nullOnDelete();
            $table->timestamp('inspected_at');
            $table->decimal('odometer_reading', 20, 6)->nullable();
            $table->decimal('fuel_level', 5, 2)->nullable()->comment('Percentage 0.00–100.00');

            $table->json('checklist')->nullable()->comment('Structured checklist items with pass/fail/na');
            $table->json('media')->nullable()->comment('Array of file paths for inspection photos');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'rental_booking_id', 'inspection_type'], 'ri_tenant_booking_type_idx');
            $table->index(['tenant_id', 'asset_id', 'inspected_at'], 'ri_tenant_asset_date_idx');
        });

        Schema::create('rental_incidents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');

            $table->foreignId('rental_booking_id')->constrained('rental_bookings', 'id', 'rin_booking_id_fk')->cascadeOnDelete();
            $table->foreignId('asset_id')->constrained('assets', 'id', 'rin_asset_id_fk')->cascadeOnDelete();
            $table->enum('incident_type', ['accident', 'theft', 'breakdown', 'traffic_violation', 'damage', 'other']);
            $table->enum('liability_party', ['customer', 'driver', 'third_party', 'company']);
            $table->text('description');
            $table->timestamp('incident_at');
            $table->foreignId('reported_by')->nullable()->constrained('users', 'id', 'rin_reported_by_fk')->nullOnDelete();
            $table->decimal('estimated_cost', 20, 6)->default('0.000000');
            $table->decimal('settled_cost', 20, 6)->nullable();
            $table->enum('status', ['reported', 'under_investigation', 'settled', 'closed'])->default('reported');
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries', 'id', 'rin_journal_entry_id_fk')->nullOnDelete();
            $table->json('media')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'rental_booking_id', 'status'], 'rin_tenant_booking_status_idx');
            $table->index(['tenant_id', 'asset_id', 'incident_at'], 'rin_tenant_asset_date_idx');
        });

        Schema::create('rental_penalties', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');

            $table->foreignId('rental_booking_id')->constrained('rental_bookings', 'id', 'rp_booking_id_fk')->cascadeOnDelete();
            $table->foreignId('asset_id')->nullable()->constrained('assets', 'id', 'rp_asset_id_fk')->nullOnDelete();
            $table->foreignId('incident_id')->nullable()->constrained('rental_incidents', 'id', 'rp_incident_id_fk')->nullOnDelete();
            $table->enum('penalty_type', ['late_return', 'damage', 'traffic_violation', 'fuel_shortage', 'excess_mileage', 'custom']);
            $table->text('description')->nullable();
            $table->decimal('amount', 20, 6)->default('0.000000');
            $table->decimal('tax_amount', 20, 6)->default('0.000000');
            $table->enum('status', ['pending', 'invoiced', 'paid', 'waived'])->default('pending');
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries', 'id', 'rp_journal_entry_id_fk')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'rental_booking_id', 'status'], 'rp_tenant_booking_status_idx');
        });

        Schema::create('rental_deposits', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');

            $table->foreignId('rental_booking_id')->constrained('rental_bookings', 'id', 'rd_booking_id_fk')->cascadeOnDelete();
            $table->enum('transaction_type', ['collection', 'refund', 'forfeit', 'adjustment']);
            $table->decimal('amount', 20, 6);
            $table->foreignId('currency_id')->nullable()->constrained('currencies', 'id', 'rd_currency_id_fk')->nullOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained('payments', 'id', 'rd_payment_id_fk')->nullOnDelete();
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries', 'id', 'rd_journal_entry_id_fk')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'rental_booking_id', 'transaction_type'], 'rd_tenant_booking_type_idx');
        });

        Schema::create('rental_revenue_splits', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');

            $table->foreignId('rental_booking_id')->constrained('rental_bookings', 'id', 'rrs_booking_id_fk')->cascadeOnDelete();
            $table->enum('split_type', ['percentage', 'fixed']);
            $table->string('counterparty_type')->comment('supplier, employee, partner');
            $table->unsignedBigInteger('counterparty_id');
            $table->decimal('split_value', 20, 6)->comment('Percentage (0-100) or fixed amount');
            $table->decimal('calculated_amount', 20, 6)->nullable();
            $table->enum('status', ['pending', 'approved', 'paid'])->default('pending');
            $table->foreignId('payment_id')->nullable()->constrained('payments', 'id', 'rrs_payment_id_fk')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'rental_booking_id'], 'rrs_tenant_booking_idx');
            $table->index(['tenant_id', 'counterparty_type', 'counterparty_id', 'status'], 'rrs_tenant_counterparty_status_idx');
        });

        Schema::create('rental_expenses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');

            $table->foreignId('rental_booking_id')->constrained('rental_bookings', 'id', 're_booking_id_fk')->cascadeOnDelete();
            $table->foreignId('asset_id')->nullable()->constrained('assets', 'id', 're_asset_id_fk')->nullOnDelete();
            $table->enum('expense_category', ['fuel', 'toll', 'parking', 'maintenance', 'insurance', 'other']);
            $table->text('description')->nullable();
            $table->decimal('amount', 20, 6);
            $table->enum('reimbursable_to', ['driver', 'supplier', 'company'])->default('company');
            $table->boolean('is_reimbursed')->default(false);
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries', 'id', 're_journal_entry_id_fk')->nullOnDelete();
            $table->string('receipt_path')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'rental_booking_id', 'expense_category'], 're_tenant_booking_category_idx');
        });

        Schema::create('rental_payouts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');

            $table->enum('payee_type', ['supplier', 'employee']);
            $table->unsignedBigInteger('payee_id');
            $table->foreignId('rental_booking_id')->nullable()->constrained('rental_bookings', 'id', 'rpay_booking_id_fk')->nullOnDelete();
            $table->string('reference_number')->nullable();
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('gross_amount', 20, 6)->default('0.000000');
            $table->decimal('deduction_amount', 20, 6)->default('0.000000');
            $table->decimal('net_amount', 20, 6)->default('0.000000');
            $table->enum('status', ['pending', 'approved', 'paid', 'cancelled'])->default('pending');
            $table->foreignId('payment_id')->nullable()->constrained('payments', 'id', 'rpay_payment_id_fk')->nullOnDelete();
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries', 'id', 'rpay_journal_entry_id_fk')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'payee_type', 'payee_id', 'status'], 'rpay_tenant_payee_status_idx');
            $table->index(['tenant_id', 'status', 'period_end'], 'rpay_tenant_status_period_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_payouts');
        Schema::dropIfExists('rental_expenses');
        Schema::dropIfExists('rental_revenue_splits');
        Schema::dropIfExists('rental_deposits');
        Schema::dropIfExists('rental_penalties');
        Schema::dropIfExists('rental_incidents');
        Schema::dropIfExists('rental_inspections');
        Schema::dropIfExists('rental_driver_assignments');
    }
};
