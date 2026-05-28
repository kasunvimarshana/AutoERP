<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_rental_settings', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();

            $table->foreignId('rental_invoice_document_definition_id')
                ->nullable()
                ->constrained('document_definitions')
                ->nullOnDelete();
            $table->foreignId('rental_agreement_document_definition_id')
                ->nullable()
                ->constrained('document_definitions')
                ->nullOnDelete();
            $table->foreignId('running_chart_document_definition_id')
                ->nullable()
                ->constrained('document_definitions')
                ->nullOnDelete();
            $table->foreignId('rental_replacement_document_definition_id')
                ->nullable()
                ->constrained('document_definitions')
                ->nullOnDelete();

            $table->foreignId('default_rental_income_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('default_driver_income_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('default_extra_charge_income_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('default_customer_receivable_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('default_provider_payable_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('default_provider_expense_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('default_tax_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('default_discount_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('default_refund_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('default_write_off_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('default_price_list_id')->nullable()->constrained('price_lists')->nullOnDelete();
            $table->foreignId('default_tax_group_id')->nullable()->constrained('tax_groups')->nullOnDelete();
            $table->foreignId('default_currency_id')->nullable()->constrained('currencies')->nullOnDelete();
            $table->foreignId('default_payment_term_id')->nullable()->constrained('payment_terms')->nullOnDelete();

            $table->boolean('allow_external_provider_vehicle')->default(true);
            $table->boolean('allow_replacement_vehicle')->default(true);
            $table->boolean('allow_without_driver')->default(true);
            $table->boolean('allow_with_driver')->default(true);
            $table->decimal('default_daily_hours', 20, 4)->default(8);
            $table->decimal('default_monthly_km_limit', 20, 4)->default(0);
            $table->decimal('default_extra_km_rate', 20, 4)->default(0);
            $table->decimal('default_extra_hour_rate', 20, 4)->default(0);
            $table->decimal('default_night_shift_rate', 20, 4)->default(0);
            $table->decimal('default_weekend_rate_multiplier', 20, 4)->default(1);
            $table->decimal('default_holiday_rate_multiplier', 20, 4)->default(1);
            $table->decimal('default_double_rate_multiplier', 20, 4)->default(2);
            $table->string('default_overtime_rule')->nullable();
            $table->string('default_night_shift_rule')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(
                ['tenant_id', 'organization_unit_id'],
                'vehicle_rental_settings_tenant_org_unit_uk',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_rental_settings');
    }
};
