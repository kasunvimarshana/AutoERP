<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->string('asset_code');
            $table->string('name');
            $table->enum('asset_kind', ['vehicle', 'equipment', 'tool', 'generic'])->default('vehicle');
            $table->enum('usage_profile', ['rent_only', 'service_only', 'dual_use', 'internal_only'])->default('dual_use');
            $table->enum('ownership_type', ['owned', 'third_party', 'leased'])->default('owned');
            $table->foreignId('owner_supplier_id')->nullable()->constrained('suppliers', 'id', 'assets_owner_supplier_id_fk')->nullOnDelete();
            $table->string('registration_number')->nullable();
            $table->string('vin')->nullable();
            $table->string('manufacturer')->nullable();
            $table->string('model')->nullable();
            $table->unsignedSmallInteger('model_year')->nullable();
            $table->string('color')->nullable();
            $table->decimal('current_meter_reading', 20, 6)->default(0);
            $table->string('meter_unit', 12)->default('km');
            $table->enum('status', ['active', 'inactive', 'retired'])->default('active');
            $table->date('commissioned_on')->nullable();
            $table->date('retired_on')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'org_unit_id', 'asset_code'], 'assets_tenant_code_uk');
            $table->unique(['tenant_id', 'org_unit_id', 'registration_number'], 'assets_tenant_registration_uk');
            $table->unique(['tenant_id', 'org_unit_id', 'vin'], 'assets_tenant_vin_uk');
            $table->index(['tenant_id', 'usage_profile', 'status'], 'assets_tenant_usage_status_idx');
            $table->index(['tenant_id', 'asset_kind'], 'assets_tenant_kind_idx');
        });

        Schema::create('asset_usage_policies', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('asset_id')->constrained('assets', 'id', 'asset_usage_policies_asset_id_fk')->cascadeOnDelete();
            $table->boolean('allow_rental')->default(true);
            $table->boolean('allow_service')->default(true);
            $table->boolean('allow_internal_use')->default(false);
            $table->boolean('requires_driver_when_rented')->default(false);
            $table->unsignedInteger('minimum_rental_hours')->default(0);
            $table->unsignedInteger('buffer_minutes_between_bookings')->default(0);
            $table->timestamp('effective_from')->nullable();
            $table->timestamp('effective_to')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'org_unit_id', 'asset_id', 'effective_from'], 'asset_usage_policies_effective_uk');
            $table->index(['tenant_id', 'asset_id', 'effective_to'], 'asset_usage_policies_tenant_asset_effective_to_idx');
        });

        Schema::create('asset_meter_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('asset_id')->constrained('assets', 'id', 'asset_meter_logs_asset_id_fk')->cascadeOnDelete();
            $table->nullableMorphs('source');
            $table->decimal('meter_reading', 20, 6);
            $table->string('meter_unit', 12)->default('km');
            $table->timestamp('recorded_at')->useCurrent();
            $table->foreignId('recorded_by')->nullable()->constrained('users', 'id', 'asset_meter_logs_recorded_by_fk')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'asset_id', 'recorded_at'], 'asset_meter_logs_tenant_asset_recorded_idx');
            $table->index(['tenant_id', 'source_type', 'source_id'], 'asset_meter_logs_tenant_source_idx');
        });

        Schema::create('asset_availability_states', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('asset_id')->constrained('assets', 'id', 'asset_availability_states_asset_id_fk')->cascadeOnDelete();
            $table->enum('availability_status', ['available', 'reserved', 'rented', 'in_service', 'internal_use', 'blocked'])->default('available');
            $table->string('reason_code')->nullable();
            $table->nullableMorphs('source');
            $table->timestamp('effective_from')->useCurrent();
            $table->timestamp('effective_to')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users', 'id', 'asset_availability_states_updated_by_fk')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'org_unit_id', 'asset_id'], 'asset_availability_states_asset_uk');
            $table->index(['tenant_id', 'availability_status'], 'asset_availability_states_tenant_status_idx');
            $table->index(['tenant_id', 'source_type', 'source_id'], 'asset_availability_states_tenant_source_idx');
        });

        Schema::create('asset_availability_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('asset_id')->constrained('assets', 'id', 'asset_availability_events_asset_id_fk')->cascadeOnDelete();
            $table->enum('from_status', ['available', 'reserved', 'rented', 'in_service', 'internal_use', 'blocked'])->nullable();
            $table->enum('to_status', ['available', 'reserved', 'rented', 'in_service', 'internal_use', 'blocked']);
            $table->string('reason_code')->nullable();
            $table->nullableMorphs('source');
            $table->foreignId('changed_by')->nullable()->constrained('users', 'id', 'asset_availability_events_changed_by_fk')->nullOnDelete();
            $table->timestamp('changed_at')->useCurrent();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'asset_id', 'changed_at'], 'asset_availability_events_tenant_asset_changed_idx');
            $table->index(['tenant_id', 'to_status', 'changed_at'], 'asset_availability_events_tenant_status_changed_idx');
        });

        Schema::create('asset_financial_flows', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->nullableMorphs('source');
            $table->enum('flow_category', [
                'rental_charge',
                'rental_deposit',
                'rental_settlement',
                'rental_expense',
                'service_charge',
                'service_return',
                'warranty_claim',
                'intercompany_billing',
            ]);
            $table->enum('direction', ['inbound', 'outbound', 'internal']);
            $table->foreignId('currency_id')->constrained('currencies', 'id', 'asset_financial_flows_currency_id_fk');
            $table->decimal('amount', 20, 6);
            $table->decimal('tax_amount', 20, 6)->default(0);
            $table->decimal('total_amount', 20, 6);
            $table->enum('status', ['draft', 'posted', 'settled', 'voided'])->default('draft');
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries', 'id', 'asset_financial_flows_journal_entry_id_fk')->nullOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained('payments', 'id', 'asset_financial_flows_payment_id_fk')->nullOnDelete();
            $table->foreignId('reversal_of_id')->nullable()->constrained('asset_financial_flows', 'id', 'asset_financial_flows_reversal_of_id_fk')->nullOnDelete();
            $table->string('rollback_group')->nullable()->comment('Group key used to rollback related transactional postings atomically.');
            $table->string('idempotency_key')->nullable()->comment('Caller supplied key to avoid duplicate financial postings during retries.');
            $table->timestamp('posted_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'org_unit_id', 'idempotency_key'], 'asset_financial_flows_tenant_idempotency_uk');
            $table->index(['tenant_id', 'status', 'posted_at'], 'asset_financial_flows_tenant_status_posted_idx');
            $table->index(['tenant_id', 'source_type', 'source_id'], 'asset_financial_flows_tenant_source_idx');
            $table->index(['tenant_id', 'rollback_group'], 'asset_financial_flows_tenant_rollback_group_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_financial_flows');
        Schema::dropIfExists('asset_availability_events');
        Schema::dropIfExists('asset_availability_states');
        Schema::dropIfExists('asset_meter_logs');
        Schema::dropIfExists('asset_usage_policies');
        Schema::dropIfExists('assets');
    }
};
