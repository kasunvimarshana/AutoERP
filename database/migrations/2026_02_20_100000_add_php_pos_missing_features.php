<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Barcodes (label templates for products) ───────────────────────
        Schema::create('barcodes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name', 150);
            $table->string('type', 30)->default('C128A')
                ->comment('Barcode type: C128A, C128B, C128C, EAN13, EAN8, UPCA, UPCE, etc.');
            $table->decimal('width', 8, 2)->default(1.00)
                ->comment('Width in mm');
            $table->decimal('height', 8, 2)->default(1.00)
                ->comment('Height in mm');
            $table->unsignedInteger('no_of_prints')->default(1);
            $table->boolean('is_default')->default(false);
            $table->string('sticker_size', 30)->default('product-small')
                ->comment('product-small | product-large | custom');
            $table->json('settings')->nullable()
                ->comment('Extra label layout settings');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'is_default']);
        });

        // ── Invoice Layouts (customisable invoice templates) ──────────────
        Schema::create('invoice_layouts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name', 150);
            $table->string('header_text', 255)->nullable();
            $table->string('footer_text', 255)->nullable();
            $table->boolean('show_business_name')->default(true);
            $table->boolean('show_location_name')->default(true);
            $table->boolean('show_mobile_number')->default(true);
            $table->boolean('show_address')->default(true);
            $table->boolean('show_email')->default(false);
            $table->boolean('show_tax_1')->default(true);
            $table->boolean('show_tax_2')->default(false);
            $table->boolean('show_barcode')->default(true);
            $table->boolean('show_customer')->default(true);
            $table->boolean('show_client_id')->default(false);
            $table->boolean('show_credit_limit')->default(false);
            $table->boolean('show_expiry_date')->default(false);
            $table->boolean('show_lot_number')->default(false);
            $table->string('design', 30)->default('classic')
                ->comment('classic | modern | simple | receipt');
            $table->string('invoice_no_prefix', 20)->nullable();
            $table->string('cn_no_prefix', 20)->nullable();
            $table->boolean('is_default')->default(false);
            $table->json('module_info')->nullable()
                ->comment('Module-specific layout overrides');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'is_default']);
        });

        // ── Printers (thermal/network receipt printers) ───────────────────
        Schema::create('printers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('business_location_id')->nullable()->constrained('business_locations')->nullOnDelete();
            $table->string('name', 150);
            $table->string('connection_type', 30)->default('network')
                ->comment('network | windows | linux | browser');
            $table->string('capability_profile', 30)->default('default')
                ->comment('default | simple | SP2000 | TEP-200M | P822D');
            $table->string('char_per_line', 10)->nullable()
                ->comment('Characters per line for receipt width');
            $table->string('ip_address', 45)->nullable();
            $table->unsignedSmallInteger('port')->nullable()->default(9100);
            $table->string('path', 255)->nullable()
                ->comment('Path for Windows/Linux printer connection');
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'business_location_id']);
        });

        // ── Product Racks (shelf/aisle locations within a warehouse) ──────
        Schema::create('product_racks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('business_location_id')->constrained('business_locations')->cascadeOnDelete();
            $table->foreignUuid('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('rack', 100)->nullable();
            $table->string('row', 100)->nullable();
            $table->string('position', 100)->nullable();
            $table->timestamps();

            $table->unique(['business_location_id', 'product_id'], 'uidx_rack_loc_product');
            $table->index(['tenant_id', 'business_location_id']);
        });

        // ── Restaurant Tables ─────────────────────────────────────────────
        Schema::create('restaurant_tables', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('business_location_id')->constrained('business_locations')->cascadeOnDelete();
            $table->string('name', 100);
            $table->unsignedTinyInteger('capacity')->default(2);
            $table->string('description', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'business_location_id', 'is_active']);
        });

        // ── Modifier Sets (add-ons / options for menu items) ──────────────
        Schema::create('modifier_sets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('business_location_id')->constrained('business_locations')->cascadeOnDelete();
            $table->string('name', 150);
            $table->string('type', 30)->default('optional')
                ->comment('optional | required | single | multiple');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'business_location_id']);
        });

        // ── Modifier Options (individual choices within a modifier set) ───
        Schema::create('modifier_options', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('modifier_set_id')->constrained('modifier_sets')->cascadeOnDelete();
            $table->string('name', 150);
            $table->decimal('price', 20, 8)->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('modifier_set_id');
        });

        // ── Product–Modifier Set pivot ────────────────────────────────────
        Schema::create('product_modifier_sets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignUuid('modifier_set_id')->constrained('modifier_sets')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['product_id', 'modifier_set_id']);
        });

        // ── Restaurant Bookings ───────────────────────────────────────────
        Schema::create('bookings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('business_location_id')->constrained('business_locations')->cascadeOnDelete();
            $table->foreignUuid('restaurant_table_id')->nullable()->constrained('restaurant_tables')->nullOnDelete();
            $table->foreignUuid('customer_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->foreignUuid('correspondent_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('waiter_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('booking_start');
            $table->dateTime('booking_end')->nullable();
            $table->unsignedTinyInteger('no_of_persons')->default(1);
            $table->string('status', 30)->default('booked')
                ->comment('booked | seated | completed | cancelled');
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'business_location_id', 'booking_start']);
            $table->index(['tenant_id', 'status']);
        });

        // ── Purchase Returns ──────────────────────────────────────────────
        Schema::create('purchase_returns', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('business_location_id')->constrained('business_locations')->cascadeOnDelete();
            $table->foreignUuid('purchase_id')->constrained('purchases')->cascadeOnDelete();
            $table->string('reference_no', 80)->nullable();
            $table->foreignUuid('supplier_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->date('return_date');
            $table->decimal('subtotal', 20, 8)->default(0);
            $table->decimal('tax_amount', 20, 8)->default(0);
            $table->decimal('total', 20, 8)->default(0);
            $table->string('status', 30)->default('completed')
                ->comment('completed | cancelled');
            $table->text('notes')->nullable();
            $table->foreignUuid('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'purchase_id']);
            $table->index(['tenant_id', 'return_date']);
        });

        // ── Purchase Return Lines ─────────────────────────────────────────
        Schema::create('purchase_return_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('purchase_return_id')->constrained('purchase_returns')->cascadeOnDelete();
            $table->foreignUuid('purchase_line_id')->constrained('purchase_lines')->cascadeOnDelete();
            $table->foreignUuid('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignUuid('product_variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->decimal('quantity', 20, 8);
            $table->decimal('unit_cost', 20, 8);
            $table->decimal('tax_percent', 8, 4)->default(0);
            $table->decimal('tax_amount', 20, 8)->default(0);
            $table->decimal('line_total', 20, 8);
            $table->timestamps();

            $table->index('purchase_return_id');
        });

        // ── Sales Commission Agent fields on users table ──────────────────
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_sales_commission_agent')->default(false)->after('metadata');
            $table->decimal('commission_rate', 8, 4)->default(0)->after('is_sales_commission_agent')
                ->comment('Commission percentage for this sales agent');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['is_sales_commission_agent', 'commission_rate']);
        });

        Schema::dropIfExists('purchase_return_lines');
        Schema::dropIfExists('purchase_returns');
        Schema::dropIfExists('bookings');
        Schema::dropIfExists('product_modifier_sets');
        Schema::dropIfExists('modifier_options');
        Schema::dropIfExists('modifier_sets');
        Schema::dropIfExists('restaurant_tables');
        Schema::dropIfExists('product_racks');
        Schema::dropIfExists('printers');
        Schema::dropIfExists('invoice_layouts');
        Schema::dropIfExists('barcodes');
    }
};
