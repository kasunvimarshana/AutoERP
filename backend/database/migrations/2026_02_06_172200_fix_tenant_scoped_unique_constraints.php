<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fix Tenant-Scoped Unique Constraints
 * 
 * This migration fixes globally-unique constraints that should be tenant-scoped
 * to prevent conflicts across different tenants.
 * 
 * Affected Tables:
 * - categories (code)
 * - customers (customer_code)
 * - suppliers (supplier_code)
 * - quotations (quote_number)
 * - invoices (invoice_number)
 * - products (sku)
 * - product_variants (sku)
 * - sales_orders (order_number)
 * - purchase_orders (po_number)
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Categories - make code unique per tenant
        Schema::table('categories', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->unique(['tenant_id', 'code'], 'categories_tenant_code_unique');
        });

        // Customers - make customer_code unique per tenant
        Schema::table('customers', function (Blueprint $table) {
            $table->dropUnique(['customer_code']);
            $table->unique(['tenant_id', 'customer_code'], 'customers_tenant_code_unique');
        });

        // Suppliers - make supplier_code unique per tenant
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropUnique(['supplier_code']);
            $table->unique(['tenant_id', 'supplier_code'], 'suppliers_tenant_code_unique');
        });

        // Quotations - make quote_number unique per tenant
        Schema::table('quotations', function (Blueprint $table) {
            $table->dropUnique(['quote_number']);
            $table->unique(['tenant_id', 'quote_number'], 'quotations_tenant_number_unique');
        });

        // Invoices - make invoice_number unique per tenant
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropUnique(['invoice_number']);
            $table->unique(['tenant_id', 'invoice_number'], 'invoices_tenant_number_unique');
        });

        // Products - make sku unique per tenant
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique(['sku']);
            $table->unique(['tenant_id', 'sku'], 'products_tenant_sku_unique');
        });

        // Product Variants - make sku unique per tenant
        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropUnique(['sku']);
            $table->unique(['tenant_id', 'sku'], 'product_variants_tenant_sku_unique');
        });

        // Sales Orders - make order_number unique per tenant
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->dropUnique(['order_number']);
            $table->unique(['tenant_id', 'order_number'], 'sales_orders_tenant_number_unique');
        });

        // Purchase Orders - make po_number unique per tenant
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropUnique(['po_number']);
            $table->unique(['tenant_id', 'po_number'], 'purchase_orders_tenant_number_unique');
        });

        // Add missing tenant_id indexes for performance
        Schema::table('stock_ledgers', function (Blueprint $table) {
            $table->index('tenant_id', 'stock_ledgers_tenant_id_index');
        });

        Schema::table('stock_levels', function (Blueprint $table) {
            $table->index('tenant_id', 'stock_levels_tenant_id_index');
        });

        Schema::table('pricing_rules', function (Blueprint $table) {
            $table->index('tenant_id', 'pricing_rules_tenant_id_index');
        });

        Schema::table('product_attributes', function (Blueprint $table) {
            $table->index('tenant_id', 'product_attributes_tenant_id_index');
        });

        // Add composite indexes for common queries
        Schema::table('products', function (Blueprint $table) {
            $table->index(['tenant_id', 'category_id'], 'products_tenant_category_index');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->index('parent_id', 'categories_parent_id_index');
        });

        Schema::table('locations', function (Blueprint $table) {
            $table->index('parent_id', 'locations_parent_id_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverse tenant-scoped unique constraints back to global
        Schema::table('categories', function (Blueprint $table) {
            $table->dropUnique('categories_tenant_code_unique');
            $table->unique('code');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropUnique('customers_tenant_code_unique');
            $table->unique('customer_code');
        });

        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropUnique('suppliers_tenant_code_unique');
            $table->unique('supplier_code');
        });

        Schema::table('quotations', function (Blueprint $table) {
            $table->dropUnique('quotations_tenant_number_unique');
            $table->unique('quote_number');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropUnique('invoices_tenant_number_unique');
            $table->unique('invoice_number');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique('products_tenant_sku_unique');
            $table->unique('sku');
            $table->dropIndex('products_tenant_category_index');
        });

        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropUnique('product_variants_tenant_sku_unique');
            $table->unique('sku');
        });

        Schema::table('sales_orders', function (Blueprint $table) {
            $table->dropUnique('sales_orders_tenant_number_unique');
            $table->unique('order_number');
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropUnique('purchase_orders_tenant_number_unique');
            $table->unique('po_number');
        });

        // Drop added indexes
        Schema::table('stock_ledgers', function (Blueprint $table) {
            $table->dropIndex('stock_ledgers_tenant_id_index');
        });

        Schema::table('stock_levels', function (Blueprint $table) {
            $table->dropIndex('stock_levels_tenant_id_index');
        });

        Schema::table('pricing_rules', function (Blueprint $table) {
            $table->dropIndex('pricing_rules_tenant_id_index');
        });

        Schema::table('product_attributes', function (Blueprint $table) {
            $table->dropIndex('product_attributes_tenant_id_index');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropIndex('categories_parent_id_index');
        });

        Schema::table('locations', function (Blueprint $table) {
            $table->dropIndex('locations_parent_id_index');
        });
    }
};
