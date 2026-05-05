<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_rate_cards', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');

            $table->string('name');
            $table->string('code')->comment('Unique human-readable code within tenant+org');
            $table->text('description')->nullable();

            // Scope (all nullable — the most specific match wins at runtime)
            $table->foreignId('asset_id')->nullable()->constrained('assets', 'id', 'rental_rate_cards_asset_id_fk')->nullOnDelete()
                ->comment('Asset-specific rate override');
            $table->foreignId('product_id')->nullable()->constrained('products', 'id', 'rental_rate_cards_product_id_fk')->nullOnDelete()
                ->comment('Product/category rate');
            $table->foreignId('customer_id')->nullable()->constrained('customers', 'id', 'rental_rate_cards_customer_id_fk')->nullOnDelete()
                ->comment('Customer-specific rate');

            // Pricing
            $table->enum('billing_uom', ['hour', 'day', 'week', 'month', 'trip'])->default('day');
            $table->decimal('rate', 20, 6)->comment('Price per billing unit');
            $table->foreignId('currency_id')->constrained('currencies', 'id', 'rental_rate_cards_currency_id_fk');
            $table->decimal('min_billing_units', 20, 6)->default('1.000000');
            $table->decimal('deposit_percentage', 20, 6)->default('0.000000')
                ->comment('Security deposit as % of rental total');
            $table->integer('priority')->default(100)->comment('Lower number = higher priority for rate resolution');

            // Validity
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');

            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'org_unit_id', 'code'], 'rental_rate_cards_tenant_org_code_uk');
            $table->index(['tenant_id', 'asset_id', 'status'], 'rental_rate_cards_tenant_asset_status_idx');
            $table->index(['tenant_id', 'customer_id', 'status'], 'rental_rate_cards_tenant_customer_status_idx');
            $table->index(['tenant_id', 'status', 'valid_from', 'valid_to'], 'rental_rate_cards_tenant_status_validity_idx');
        });

        Schema::create('rental_bookings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');

            $table->string('booking_number');
            $table->foreignId('customer_id')->constrained('customers', 'id', 'rental_bookings_customer_id_fk')->cascadeOnDelete();
            $table->enum('booking_type', ['self_drive', 'with_driver', 'mixed'])->default('self_drive');
            $table->enum('fleet_source', ['own', 'third_party', 'mixed'])->default('own');
            $table->enum('status', ['draft', 'confirmed', 'in_progress', 'completed', 'cancelled', 'overdue'])->default('draft');

            // Timeline
            $table->timestamp('scheduled_start_at');
            $table->timestamp('scheduled_end_at');
            $table->timestamp('actual_start_at')->nullable();
            $table->timestamp('actual_end_at')->nullable();

            // Finance
            $table->foreignId('billing_currency_id')->nullable()->constrained('currencies', 'id', 'rental_bookings_currency_id_fk')->nullOnDelete();
            $table->decimal('subtotal_amount', 20, 6)->default('0.000000');
            $table->decimal('discount_amount', 20, 6)->default('0.000000');
            $table->decimal('tax_amount', 20, 6)->default('0.000000');
            $table->decimal('total_amount', 20, 6)->default('0.000000');
            $table->decimal('deposit_amount', 20, 6)->default('0.000000');
            $table->enum('deposit_status', ['pending', 'partial', 'paid', 'refunded', 'forfeited'])->default('pending');

            // Finance links (created when booking is confirmed/invoiced)
            $table->foreignId('ar_transaction_id')->nullable()->constrained('ar_transactions', 'id', 'rental_bookings_ar_transaction_id_fk')->nullOnDelete();
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries', 'id', 'rental_bookings_journal_entry_id_fk')->nullOnDelete();

            // Audit
            $table->foreignId('created_by')->nullable()->constrained('users', 'id', 'rental_bookings_created_by_fk')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->text('terms_and_conditions')->nullable();
            $table->string('contract_file_path')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'org_unit_id', 'booking_number'], 'rental_bookings_tenant_org_number_uk');
            $table->index(['tenant_id', 'customer_id', 'status'], 'rental_bookings_tenant_customer_status_idx');
            $table->index(['tenant_id', 'status', 'scheduled_start_at'], 'rental_bookings_tenant_status_start_idx');
            $table->index(['tenant_id', 'status', 'scheduled_end_at'], 'rental_bookings_tenant_status_end_idx');
        });

        Schema::create('rental_booking_assets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');

            $table->foreignId('rental_booking_id')->constrained('rental_bookings', 'id', 'rba_booking_id_fk')->cascadeOnDelete();
            $table->foreignId('asset_id')->constrained('assets', 'id', 'rba_asset_id_fk')->cascadeOnDelete();
            $table->foreignId('rate_card_id')->nullable()->constrained('rental_rate_cards', 'id', 'rba_rate_card_id_fk')->nullOnDelete();

            $table->enum('billing_uom', ['hour', 'day', 'week', 'month', 'trip'])->default('day');
            $table->decimal('rate', 20, 6)->default('0.000000');
            $table->decimal('billing_units', 20, 6)->default('0.000000');
            $table->decimal('line_amount', 20, 6)->default('0.000000');
            $table->decimal('tax_amount', 20, 6)->default('0.000000');

            $table->enum('status', ['pending', 'confirmed', 'active', 'returned', 'cancelled'])->default('pending');
            $table->decimal('start_odometer', 20, 6)->nullable();
            $table->decimal('end_odometer', 20, 6)->nullable();

            $table->timestamp('scheduled_start_at')->nullable();
            $table->timestamp('scheduled_end_at')->nullable();
            $table->timestamp('actual_start_at')->nullable();
            $table->timestamp('actual_end_at')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'org_unit_id', 'rental_booking_id', 'asset_id'], 'rba_tenant_booking_asset_uk');
            $table->index(['tenant_id', 'asset_id', 'status'], 'rba_tenant_asset_status_idx');
            $table->index(['tenant_id', 'rental_booking_id', 'status'], 'rba_tenant_booking_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_booking_assets');
        Schema::dropIfExists('rental_bookings');
        Schema::dropIfExists('rental_rate_cards');
    }
};
