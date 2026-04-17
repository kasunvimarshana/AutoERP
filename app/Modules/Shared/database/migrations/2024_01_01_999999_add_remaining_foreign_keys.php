<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Purchase
        $this->addForeignIfPossible('purchase_orders', 'supplier_id', 'suppliers', 'cascade');
        $this->addForeignIfPossible('purchase_orders', 'org_unit_id', 'org_units', 'null');
        $this->addForeignIfPossible('purchase_orders', 'warehouse_id', 'warehouses', 'cascade');
        $this->addForeignIfPossible('purchase_orders', 'created_by', 'users');
        $this->addForeignIfPossible('purchase_orders', 'approved_by', 'users', 'null');

        $this->addForeignIfPossible('purchase_order_lines', 'product_id', 'products', 'cascade');
        $this->addForeignIfPossible('purchase_order_lines', 'variant_id', 'product_variants', 'null');
        $this->addForeignIfPossible('purchase_order_lines', 'uom_id', 'units_of_measure');
        $this->addForeignIfPossible('purchase_order_lines', 'tax_class_id', 'tax_classes', 'null');
        $this->addForeignIfPossible('purchase_order_lines', 'account_id', 'accounts', 'null');

        $this->addForeignIfPossible('grn_headers', 'supplier_id', 'suppliers', 'cascade');
        $this->addForeignIfPossible('grn_headers', 'warehouse_id', 'warehouses', 'cascade');
        $this->addForeignIfPossible('grn_headers', 'created_by', 'users');

        $this->addForeignIfPossible('grn_lines', 'product_id', 'products', 'cascade');
        $this->addForeignIfPossible('grn_lines', 'variant_id', 'product_variants', 'null');
        $this->addForeignIfPossible('grn_lines', 'batch_id', 'batches', 'null');
        $this->addForeignIfPossible('grn_lines', 'serial_id', 'serials', 'null');
        $this->addForeignIfPossible('grn_lines', 'location_id', 'warehouse_locations', 'cascade');
        $this->addForeignIfPossible('grn_lines', 'uom_id', 'units_of_measure');

        $this->addForeignIfPossible('purchase_invoices', 'supplier_id', 'suppliers', 'cascade');
        $this->addForeignIfPossible('purchase_invoices', 'ap_account_id', 'accounts', 'null');
        $this->addForeignIfPossible('purchase_invoices', 'journal_entry_id', 'journal_entries', 'null');

        $this->addForeignIfPossible('purchase_invoice_lines', 'product_id', 'products', 'cascade');
        $this->addForeignIfPossible('purchase_invoice_lines', 'variant_id', 'product_variants', 'null');
        $this->addForeignIfPossible('purchase_invoice_lines', 'uom_id', 'units_of_measure');
        $this->addForeignIfPossible('purchase_invoice_lines', 'account_id', 'accounts', 'null');

        $this->addForeignIfPossible('purchase_returns', 'supplier_id', 'suppliers', 'cascade');
        $this->addForeignIfPossible('purchase_returns', 'journal_entry_id', 'journal_entries', 'null');

        $this->addForeignIfPossible('purchase_return_lines', 'product_id', 'products', 'cascade');
        $this->addForeignIfPossible('purchase_return_lines', 'variant_id', 'product_variants', 'null');
        $this->addForeignIfPossible('purchase_return_lines', 'batch_id', 'batches', 'null');
        $this->addForeignIfPossible('purchase_return_lines', 'serial_id', 'serials', 'null');
        $this->addForeignIfPossible('purchase_return_lines', 'from_location_id', 'warehouse_locations', 'cascade');
        $this->addForeignIfPossible('purchase_return_lines', 'uom_id', 'units_of_measure');

        // Sales
        $this->addForeignIfPossible('sales_orders', 'customer_id', 'customers', 'cascade');
        $this->addForeignIfPossible('sales_orders', 'org_unit_id', 'org_units', 'null');
        $this->addForeignIfPossible('sales_orders', 'warehouse_id', 'warehouses', 'cascade');
        $this->addForeignIfPossible('sales_orders', 'price_list_id', 'price_lists', 'null');
        $this->addForeignIfPossible('sales_orders', 'created_by', 'users');
        $this->addForeignIfPossible('sales_orders', 'approved_by', 'users', 'null');

        $this->addForeignIfPossible('sales_order_lines', 'product_id', 'products', 'cascade');
        $this->addForeignIfPossible('sales_order_lines', 'variant_id', 'product_variants', 'null');
        $this->addForeignIfPossible('sales_order_lines', 'uom_id', 'units_of_measure');
        $this->addForeignIfPossible('sales_order_lines', 'tax_class_id', 'tax_classes', 'null');
        $this->addForeignIfPossible('sales_order_lines', 'income_account_id', 'accounts', 'null');
        $this->addForeignIfPossible('sales_order_lines', 'batch_id', 'batches', 'null');
        $this->addForeignIfPossible('sales_order_lines', 'serial_id', 'serials', 'null');

        $this->addForeignIfPossible('shipments', 'customer_id', 'customers', 'cascade');
        $this->addForeignIfPossible('shipments', 'warehouse_id', 'warehouses', 'cascade');

        $this->addForeignIfPossible('shipment_lines', 'product_id', 'products', 'cascade');
        $this->addForeignIfPossible('shipment_lines', 'variant_id', 'product_variants', 'null');
        $this->addForeignIfPossible('shipment_lines', 'batch_id', 'batches', 'null');
        $this->addForeignIfPossible('shipment_lines', 'serial_id', 'serials', 'null');
        $this->addForeignIfPossible('shipment_lines', 'from_location_id', 'warehouse_locations', 'cascade');
        $this->addForeignIfPossible('shipment_lines', 'uom_id', 'units_of_measure');

        $this->addForeignIfPossible('sales_invoices', 'customer_id', 'customers', 'cascade');
        $this->addForeignIfPossible('sales_invoices', 'ar_account_id', 'accounts', 'null');
        $this->addForeignIfPossible('sales_invoices', 'journal_entry_id', 'journal_entries', 'null');

        $this->addForeignIfPossible('sales_invoice_lines', 'product_id', 'products', 'cascade');
        $this->addForeignIfPossible('sales_invoice_lines', 'variant_id', 'product_variants', 'null');
        $this->addForeignIfPossible('sales_invoice_lines', 'uom_id', 'units_of_measure');
        $this->addForeignIfPossible('sales_invoice_lines', 'income_account_id', 'accounts', 'null');

        $this->addForeignIfPossible('sales_returns', 'customer_id', 'customers', 'cascade');
        $this->addForeignIfPossible('sales_returns', 'journal_entry_id', 'journal_entries', 'null');

        $this->addForeignIfPossible('sales_return_lines', 'product_id', 'products', 'cascade');
        $this->addForeignIfPossible('sales_return_lines', 'variant_id', 'product_variants', 'null');
        $this->addForeignIfPossible('sales_return_lines', 'batch_id', 'batches', 'null');
        $this->addForeignIfPossible('sales_return_lines', 'serial_id', 'serials', 'null');
        $this->addForeignIfPossible('sales_return_lines', 'to_location_id', 'warehouse_locations', 'cascade');
        $this->addForeignIfPossible('sales_return_lines', 'uom_id', 'units_of_measure');

        // Finance
        $this->addForeignIfPossible('journal_entries', 'created_by', 'users');
        $this->addForeignIfPossible('journal_entries', 'posted_by', 'users', 'null');
    }

    public function down(): void
    {
        // No-op.
    }

    private function addForeignIfPossible(string $tableName, string $column, string $referencedTable, string $onDelete = 'none'): void
    {
        if (!Schema::hasTable($tableName) || !Schema::hasTable($referencedTable) || !Schema::hasColumn($tableName, $column)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($column, $referencedTable, $onDelete): void {
            try {
                $foreign = $table->foreign($column)->references('id')->on($referencedTable);
                if ($onDelete === 'cascade') {
                    $foreign->cascadeOnDelete();
                } elseif ($onDelete === 'null') {
                    $foreign->nullOnDelete();
                }
            } catch (\Throwable) {
                // Ignore if constraint already exists or cannot be added in current environment.
            }
        });
    }
};
