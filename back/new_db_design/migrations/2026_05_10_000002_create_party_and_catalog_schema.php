<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('party_code', 50);
            $table->string('party_kind', 30);
            $table->string('legal_name', 200);
            $table->string('display_name', 200)->nullable();
            $table->string('tax_number', 100)->nullable();
            $table->string('registration_number', 100)->nullable();
            $table->string('status_code', 30)->default('active');
            $table->timestamps();

            $table->unique(['tenant_id', 'party_code']);
            $table->index(['tenant_id', 'legal_name']);
            $table->index(['tenant_id', 'status_code']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreign('party_id')->references('id')->on('parties')->nullOnDelete();
        });

        Schema::create('party_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('party_id')->constrained('parties')->cascadeOnDelete();
            $table->string('role_code', 50);
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->unique(['tenant_id', 'party_id', 'role_code']);
        });

        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('line_1', 255);
            $table->string('line_2', 255)->nullable();
            $table->string('city', 100);
            $table->string('state', 100)->nullable();
            $table->string('postal_code', 30)->nullable();
            $table->string('country_code', 2);
            $table->timestamps();

            $table->index(['tenant_id', 'country_code']);
        });

        Schema::create('party_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('party_id')->constrained('parties')->cascadeOnDelete();
            $table->foreignId('address_id')->constrained('addresses')->cascadeOnDelete();
            $table->string('address_role', 50);
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->unique(['tenant_id', 'party_id', 'address_id', 'address_role'], 'party_addresses_scope_uk');
        });

        Schema::create('party_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('party_id')->constrained('parties')->cascadeOnDelete();
            $table->string('contact_type', 50);
            $table->string('contact_value', 190);
            $table->string('label', 100)->nullable();
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_verified')->default(false);
            $table->timestamps();

            $table->index(['tenant_id', 'party_id', 'contact_type']);
        });

        Schema::create('tax_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('party_id')->constrained('parties')->cascadeOnDelete();
            $table->string('country_code', 2);
            $table->string('tax_type', 50);
            $table->string('registration_number', 100);
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->unique(['tenant_id', 'party_id', 'country_code', 'tax_type', 'registration_number'], 'tax_registrations_scope_uk');
        });

        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('code', 3);
            $table->string('name', 100);
            $table->string('symbol', 10)->nullable();
            $table->unsignedTinyInteger('minor_units')->default(2);
            $table->boolean('is_base_currency')->default(false);
            $table->timestamps();

            $table->unique(['tenant_id', 'code']);
        });

        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('base_currency_id')->constrained('currencies')->cascadeOnDelete();
            $table->foreignId('quote_currency_id')->constrained('currencies')->cascadeOnDelete();
            $table->date('rate_date');
            $table->decimal('rate', 20, 10);
            $table->string('source_code', 50)->default('manual');
            $table->timestamps();

            $table->unique(['tenant_id', 'base_currency_id', 'quote_currency_id', 'rate_date'], 'exchange_rates_scope_uk');
        });

        Schema::create('uoms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('code', 30);
            $table->string('name', 100);
            $table->string('category_code', 50);
            $table->unsignedTinyInteger('precision_scale')->default(2);
            $table->timestamps();

            $table->unique(['tenant_id', 'code']);
        });

        Schema::create('uom_conversions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('from_uom_id')->constrained('uoms')->cascadeOnDelete();
            $table->foreignId('to_uom_id')->constrained('uoms')->cascadeOnDelete();
            $table->decimal('multiplier', 20, 10)->default(1);
            $table->decimal('divisor', 20, 10)->default(1);
            $table->timestamps();

            $table->unique(['tenant_id', 'from_uom_id', 'to_uom_id'], 'uom_conversions_scope_uk');
        });

        Schema::create('product_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('product_categories')->nullOnDelete();
            $table->string('category_code', 50);
            $table->string('category_name', 150);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['tenant_id', 'category_code']);
            $table->index(['tenant_id', 'parent_id']);
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('product_category_id')->nullable()->constrained('product_categories')->nullOnDelete();
            $table->foreignId('default_uom_id')->nullable()->constrained('uoms')->nullOnDelete();
            $table->string('product_code', 50);
            $table->string('product_name', 200);
            $table->string('product_kind', 30)->default('goods');
            $table->string('valuation_method', 30)->default('fifo');
            $table->string('tracking_mode', 30)->default('none');
            $table->boolean('is_stock_item')->default(true);
            $table->boolean('is_sellable')->default(true);
            $table->boolean('is_purchasable')->default(true);
            $table->timestamps();

            $table->unique(['tenant_id', 'product_code']);
            $table->index(['tenant_id', 'product_kind']);
        });

        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('sku', 100);
            $table->string('barcode', 100)->nullable();
            $table->string('variant_name', 150)->nullable();
            $table->string('status_code', 30)->default('active');
            $table->decimal('weight', 20, 6)->nullable();
            $table->decimal('volume', 20, 6)->nullable();
            $table->decimal('standard_cost', 20, 6)->default(0);
            $table->timestamps();

            $table->unique(['tenant_id', 'sku']);
            $table->index(['tenant_id', 'product_id']);
            $table->index(['tenant_id', 'status_code']);
        });

        Schema::create('product_identifiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->string('identifier_type', 50);
            $table->string('identifier_value', 150);
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->unique(['tenant_id', 'identifier_type', 'identifier_value'], 'product_identifiers_scope_uk');
            $table->index(['tenant_id', 'product_variant_id']);
        });

        Schema::create('price_lists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('currency_id')->constrained('currencies')->cascadeOnDelete();
            $table->string('price_list_code', 50);
            $table->string('price_list_name', 150);
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['tenant_id', 'price_list_code']);
            $table->index(['tenant_id', 'is_active']);
        });

        Schema::create('price_list_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('price_list_id')->constrained('price_lists')->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->decimal('min_quantity', 20, 6)->default(1);
            $table->decimal('unit_price', 20, 6);
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'price_list_id', 'product_variant_id', 'min_quantity'], 'price_list_items_scope_uk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_list_items');
        Schema::dropIfExists('price_lists');
        Schema::dropIfExists('product_identifiers');
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('products');
        Schema::dropIfExists('product_categories');
        Schema::dropIfExists('uom_conversions');
        Schema::dropIfExists('uoms');
        Schema::dropIfExists('exchange_rates');
        Schema::dropIfExists('currencies');
        Schema::dropIfExists('tax_registrations');
        Schema::dropIfExists('party_contacts');
        Schema::dropIfExists('party_addresses');
        Schema::dropIfExists('addresses');
        Schema::dropIfExists('party_roles');
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['party_id']);
        });
        Schema::dropIfExists('parties');
    }
};
