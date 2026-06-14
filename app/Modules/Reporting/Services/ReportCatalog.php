<?php

declare(strict_types=1);

namespace Modules\Reporting\Services;

use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Customer\Models\Customer;
use Modules\Finance\Models\FinanceAccount;
use Modules\Finance\Models\FinanceAccountBalance;
use Modules\Finance\Models\FinanceBankReconciliation;
use Modules\Finance\Models\FinanceBudget;
use Modules\Finance\Models\FinanceJournalEntry;
use Modules\Finance\Models\FinanceLedgerEntry;
use Modules\Hr\Models\HrEmployee;
use Modules\Inventory\Models\InventoryAllocation;
use Modules\Inventory\Models\InventoryAllocationIssue;
use Modules\Inventory\Models\InventoryBatch;
use Modules\Inventory\Models\InventoryMovement;
use Modules\Inventory\Models\InventoryReservation;
use Modules\Inventory\Models\InventorySerialNumber;
use Modules\Inventory\Models\InventoryStockBalance;
use Modules\Inventory\Models\InventoryValuationConsumption;
use Modules\Inventory\Models\InventoryValuationLayer;
use Modules\Invoice\Models\Invoice;
use Modules\Invoice\Models\InvoiceBalance;
use Modules\Item\Models\Item;
use Modules\Payment\Models\Payment;
use Modules\Payment\Models\PaymentAllocation;
use Modules\Payment\Models\PaymentLine;
use Modules\Payment\Models\PaymentRefund;
use Modules\Payment\Models\PaymentReversal;
use Modules\Payment\Models\PaymentUnappliedBalance;
use Modules\Purchase\Models\GoodsReceiptNote;
use Modules\Purchase\Models\PurchaseDebitNote;
use Modules\Purchase\Models\PurchaseOrder;
use Modules\Purchase\Models\PurchaseReturn;
use Modules\Reporting\DTOs\ReportColumn;
use Modules\Reporting\DTOs\ReportDefinition;
use Modules\Reporting\DTOs\ReportFilter;
use Modules\Supplier\Models\Supplier;
use Modules\Supplier\Models\SupplierItemMapping;
use Modules\Tax\Models\TaxTransaction;
use Modules\UOM\Models\UnitOfMeasureModel;
use Modules\Vehicle\Models\Vehicle;
use Modules\Vehicle\Models\VehicleDocument;
use Modules\VehicleRental\Models\RentalAgreement;
use Modules\VehicleRental\Models\RentalAgreementVehicle;
use Modules\VehicleRental\Models\RentalAgreementVehicleLink;
use Modules\VehicleRental\Models\RentalCharge;
use Modules\VehicleRental\Models\RentalExpense;
use Modules\VehicleRental\Models\RentalPaymentLink;
use Modules\VehicleRental\Models\RentalUsageContext;
use Modules\VehicleRental\Models\RentalUsageEvent;
use Modules\VehicleRental\Models\RentalUsageLog;
use Modules\VehicleService\Models\VehicleServiceJob;
use Modules\VehicleService\Models\VehicleServiceJobLine;
use Modules\VehicleService\Models\VehicleServiceLineEmployee;
use Modules\VehicleService\Models\VehicleServiceStatusHistory;
use Modules\Warehouse\Models\WarehouseModel;

final class ReportCatalog
{
    /** @var array<string, ReportDefinition>|null */
    private ?array $reports = null;

    public function __construct(
        private readonly VehicleServiceProfitabilityCalculator $profitability,
        private readonly DecimalMath $math,
    ) {}

    /**
     * @return array<string, ReportDefinition>
     */
    public function all(): array
    {
        return $this->reports ??= $this->build();
    }

    public function get(string $key): ReportDefinition
    {
        return $this->all()[$key] ?? throw new InvalidArgumentException("Report [{$key}] is not defined.");
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function index(): array
    {
        return array_values(array_map(fn (ReportDefinition $report): array => $report->toArray(), $this->all()));
    }

    /**
     * @return array<string, ReportDefinition>
     */
    private function build(): array
    {
        $reports = [
            $this->masters('masters.uom', 'UOM', UnitOfMeasureModel::class, [
                $this->col('code', 'Code', sort: 'code'),
                $this->col('name', 'Name', sort: 'name'),
                $this->col('category', 'Category', sort: 'category'),
                $this->col('decimal_precision', 'Precision', sort: 'decimal_precision'),
                $this->col('is_base', 'Base', format: 'boolean', sort: 'is_base'),
                $this->col('is_active', 'Active', format: 'boolean', sort: 'is_active'),
            ], ['code', 'name', 'category']),
            $this->masters('masters.item', 'Item', Item::class, [
                $this->col('code', 'Code', sort: 'code'),
                $this->col('name', 'Name', sort: 'name'),
                $this->col('item_type', 'Type', format: 'enum', sort: 'item_type'),
                $this->col('category', 'Category', 'category.name'),
                $this->col('brand', 'Brand', 'brand.name'),
                $this->col('base_uom', 'Base UOM', 'baseUom.code'),
                $this->col('tracking_type', 'Tracking', format: 'enum', sort: 'tracking_type'),
                $this->col('is_stockable', 'Stockable', format: 'boolean', sort: 'is_stockable'),
                $this->col('is_active', 'Active', format: 'boolean', sort: 'is_active'),
            ], ['code', 'name', 'category.name', 'brand.name'], ['category', 'brand', 'baseUom']),
            $this->party('masters.supplier', 'Supplier Report', Supplier::class),
            $this->party('masters.customer', 'Customer Report', Customer::class),
            $this->masters('masters.vehicle', 'Vehicle Report', Vehicle::class, [
                $this->col('vehicle_number', 'Vehicle No.', sort: 'vehicle_number'),
                $this->col('code', 'Code', sort: 'code'),
                $this->col('registration_number', 'Registration', sort: 'registration_number'),
                $this->col('make', 'Make', 'make.name'),
                $this->col('model', 'Model', 'model.name'),
                $this->col('customer', 'Customer', 'customer.name'),
                $this->col('status', 'Status', format: 'enum', sort: 'status'),
            ], ['vehicle_number', 'code', 'registration_number', 'make.name', 'model.name', 'customer.name'], ['make', 'model', 'customer']),
            $this->masters('masters.employee', 'Employee Report', HrEmployee::class, [
                $this->col('employee_number', 'Employee No.', sort: 'employee_number'),
                $this->col('display_name', 'Name', sort: 'display_name'),
                $this->col('department', 'Department', 'department.name'),
                $this->col('designation', 'Designation', 'designation.name'),
                $this->col('status', 'Status', format: 'enum', sort: 'status'),
                $this->col('availability_status', 'Availability', format: 'enum', sort: 'availability_status'),
                $this->col('joined_date', 'Joined', format: 'date', sort: 'joined_date'),
            ], ['employee_number', 'display_name', 'email', 'department.name', 'designation.name'], ['department', 'designation'], 'joined_date'),
            $this->masters('masters.warehouse', 'Warehouse', WarehouseModel::class, [
                $this->col('code', 'Code', sort: 'code'),
                $this->col('name', 'Name', sort: 'name'),
                $this->col('warehouse_type', 'Type', sort: 'warehouse_type'),
                $this->col('is_default', 'Default', format: 'boolean', sort: 'is_default'),
                $this->col('is_active', 'Active', format: 'boolean', sort: 'is_active'),
            ], ['code', 'name', 'warehouse_type']),

            $this->inventory('inventory.stock-balance', 'Stock Balance', InventoryStockBalance::class, [
                $this->itemCol(), $this->col('warehouse', 'Warehouse', 'warehouse.name'), $this->col('location', 'Location', 'warehouseLocation.name'),
                $this->col('uom', 'UOM', 'item.baseUom.code'),
                $this->col('batch', 'Batch', 'batch.batch_number'), $this->qty('quantity_on_hand', 'On Hand'), $this->qty('quantity_reserved', 'Reserved'),
                $this->qty('quantity_allocated', 'Allocated'), $this->qty('quantity_available', 'Available'), $this->qty('quantity_in_transit', 'In Transit'),
                $this->qty('quantity_damaged', 'Damaged'), $this->qty('quantity_quarantine', 'Quarantine'), $this->qty('quantity_expired', 'Expired'), $this->money('total_value', 'Value'),
            ], ['item.code', 'item.name', 'warehouse.name', 'batch.batch_number'], ['item', 'item.baseUom', 'warehouse', 'warehouseLocation', 'batch']),
            $this->inventory('inventory.low-stock', 'Low Stock Report', InventoryStockBalance::class, [
                $this->itemCol(), $this->col('warehouse', 'Warehouse', 'warehouse.name'), $this->col('location', 'Location', 'warehouseLocation.name'),
                $this->qty('quantity_available', 'Available'), $this->col('uom', 'UOM', 'item.baseUom.code'),
                new ReportColumn(
                    key: 'low_stock_threshold',
                    label: 'Low Stock Threshold',
                    format: 'decimal',
                    value: static fn (InventoryStockBalance $balance): string => is_array($balance->item?->metadata)
                        ? (string) (data_get($balance->item->metadata, 'inventory.low_stock_threshold', $balance->item->metadata['low_stock_threshold'] ?? '0.000000'))
                        : '0.000000',
                ),
            ], ['item.code', 'item.name', 'warehouse.name'], ['item', 'item.baseUom', 'warehouse', 'warehouseLocation']),
            $this->inventory('inventory.stock-movement', 'Stock Movement', InventoryMovement::class, [
                $this->col('movement_date', 'Date', format: 'date', sort: 'movement_date'), $this->itemCol(), $this->col('warehouse', 'Warehouse', 'warehouse.name'),
                $this->col('movement_type', 'Type', format: 'enum', sort: 'movement_type'), $this->col('direction', 'Direction', format: 'enum', sort: 'direction'),
                $this->qty('quantity', 'Quantity'), $this->col('uom', 'UOM', 'baseUom.code'), $this->money('total_cost', 'Cost'), $this->col('source_type', 'Source', sort: 'source_type'), $this->col('status', 'Status', format: 'enum', sort: 'status'),
            ], ['movement_number', 'item.code', 'item.name', 'warehouse.name', 'source_type'], ['item', 'baseUom', 'warehouse'], 'movement_date'),
            $this->inventory('inventory.batch-lot', 'Batch/Lot', InventoryBatch::class, [
                $this->col('batch_number', 'Batch', sort: 'batch_number'), $this->itemCol(), $this->col('manufacture_date', 'Manufactured', format: 'date', sort: 'manufacture_date'),
                $this->col('expiry_date', 'Expiry', format: 'date', sort: 'expiry_date'), $this->col('status', 'Status', format: 'enum', sort: 'status'),
            ], ['batch_number', 'lot_number', 'item.code', 'item.name'], ['item'], 'expiry_date'),
            $this->inventory('inventory.batch-expiry', 'Batch Expiry Report', InventoryBatch::class, [
                $this->col('batch_number', 'Batch', sort: 'batch_number'), $this->col('lot_number', 'Lot', sort: 'lot_number'), $this->itemCol(),
                $this->col('expiry_date', 'Expiry', format: 'date', sort: 'expiry_date'),
                new ReportColumn(
                    key: 'days_to_expiry',
                    label: 'Days To Expiry',
                    value: static fn (InventoryBatch $batch): ?int => $batch->expiry_date === null
                        ? null
                        : (int) now()->startOfDay()->diffInDays($batch->expiry_date->startOfDay(), false),
                ),
                $this->col('status', 'Status', format: 'enum', sort: 'status'),
            ], ['batch_number', 'lot_number', 'item.code', 'item.name'], ['item'], 'expiry_date'),
            $this->inventory('inventory.serial', 'Serial', InventorySerialNumber::class, [
                $this->col('serial_number', 'Serial', sort: 'serial_number'), $this->itemCol(), $this->col('batch', 'Batch', 'batch.batch_number'),
                $this->col('warehouse', 'Warehouse', 'warehouse.name'), $this->col('status', 'Status', format: 'enum', sort: 'status'),
            ], ['serial_number', 'item.code', 'item.name', 'batch.batch_number', 'warehouse.name'], ['item', 'batch', 'warehouse']),
            $this->inventoryFlow('inventory.reservation', 'Reservation', InventoryReservation::class, 'reservation_date', 'quantity_reserved'),
            $this->inventoryFlow('inventory.allocation', 'Allocation', InventoryAllocation::class, 'allocation_date', 'quantity_allocated'),
            $this->inventory('inventory.valuation-layer', 'Valuation Layer', InventoryValuationLayer::class, [
                $this->itemCol(), $this->col('warehouse', 'Warehouse', 'warehouse.name'), $this->col('batch', 'Batch', 'batch.batch_number'),
                $this->col('valuation_method', 'Method', format: 'enum', sort: 'valuation_method'),
                $this->qty('original_quantity', 'Original Qty'), $this->qty('remaining_quantity', 'Remaining Qty'),
                $this->money('unit_cost', 'Unit Cost', false), $this->money('remaining_value', 'Remaining Value'),
                $this->col('status', 'Status', format: 'enum', sort: 'status'),
            ], ['item.code', 'item.name', 'warehouse.name', 'batch.batch_number'], ['item', 'warehouse', 'batch']),
            $this->inventory('inventory.valuation', 'Inventory Valuation Report', InventoryValuationLayer::class, [
                $this->itemCol(), $this->col('warehouse', 'Warehouse', 'warehouse.name'), $this->col('batch', 'Batch', 'batch.batch_number'),
                $this->col('valuation_method', 'Method', format: 'enum', sort: 'valuation_method'),
                $this->qty('remaining_quantity', 'Quantity'), $this->money('unit_cost', 'Unit Cost', false),
                $this->money('remaining_value', 'Value'), $this->col('status', 'Status', format: 'enum', sort: 'status'),
            ], ['item.code', 'item.name', 'warehouse.name', 'batch.batch_number'], ['item', 'warehouse', 'batch']),
            $this->inventory('inventory.aging', 'Inventory Aging Report', InventoryValuationLayer::class, [
                $this->itemCol(), $this->col('warehouse', 'Warehouse', 'warehouse.name'), $this->col('batch', 'Batch', 'batch.batch_number'),
                $this->col('created_at', 'Receipt Date', format: 'date', sort: 'created_at'),
                new ReportColumn(
                    key: 'age_days',
                    label: 'Age Days',
                    value: static fn (InventoryValuationLayer $layer): int => (int) $layer->created_at?->startOfDay()->diffInDays(now()->startOfDay()),
                ),
                $this->qty('remaining_quantity', 'Remaining Qty'), $this->money('remaining_value', 'Remaining Value'),
            ], ['item.code', 'item.name', 'warehouse.name', 'batch.batch_number'], ['item', 'warehouse', 'batch'], 'created_at'),
            $this->inventory('inventory.valuation-consumption', 'Valuation Consumption', InventoryValuationConsumption::class, [
                $this->col('movement', 'Issue Movement', 'issueMovement.movement_number'),
                $this->col('item', 'Item', 'valuationLayer.item.name'),
                $this->qty('quantity_consumed', 'Consumed Qty'), $this->money('unit_cost', 'Unit Cost', false),
                $this->money('total_cost', 'Consumed Cost'), $this->col('reversed_at', 'Reversed', format: 'datetime', sort: 'reversed_at'),
            ], ['issueMovement.movement_number', 'valuationLayer.item.code', 'valuationLayer.item.name'], ['issueMovement', 'valuationLayer.item']),
            $this->inventory('inventory.allocation-issue', 'Allocation Issue', InventoryAllocationIssue::class, [
                $this->col('allocation', 'Allocation', 'allocation.allocation_number'),
                $this->col('movement', 'Movement', 'movement.movement_number'),
                $this->qty('quantity_issued', 'Issued Qty'), $this->money('unit_cost', 'Unit Cost', false),
                $this->money('total_cost', 'Issue Cost'),
            ], ['allocation.allocation_number', 'movement.movement_number'], ['allocation', 'movement']),
            $this->inventory('inventory.cost', 'Inventory Cost', InventoryMovement::class, [
                $this->col('movement_date', 'Date', format: 'date', sort: 'movement_date'), $this->itemCol(),
                $this->col('movement_type', 'Type', format: 'enum', sort: 'movement_type'),
                $this->qty('quantity', 'Quantity'), $this->money('unit_cost', 'Unit Cost', false),
                $this->money('total_cost', 'Total Cost'), $this->money('balance_value_after', 'Balance Value', false),
            ], ['movement_number', 'item.code', 'item.name'], ['item'], 'movement_date'),

            $this->purchase('purchase.orders', 'Purchase Orders', PurchaseOrder::class, 'purchase_order_date', 'purchase_order_number', 'grand_total'),
            $this->purchase('purchase.grns', 'GRNs', GoodsReceiptNote::class, 'received_date', 'grn_number', 'grand_total'),
            $this->purchase('purchase.returns', 'Purchase Returns', PurchaseReturn::class, 'return_date', 'return_number', 'grand_total'),
            $this->purchase('purchase.debit-notes', 'Debit Notes', PurchaseDebitNote::class, 'debit_note_date', 'debit_note_number', 'amount'),
            $this->definition('purchase.supplier-item-mapping', 'Supplier Item Mapping', 'Purchase', SupplierItemMapping::class, [
                $this->col('supplier', 'Supplier', 'supplier.name'), $this->itemCol(), $this->col('supplier_item_code', 'Supplier Code', sort: 'supplier_item_code'),
                $this->col('supplier_item_name', 'Supplier Name', sort: 'supplier_item_name'), $this->col('uom', 'Purchase UOM', 'defaultPurchaseUom.code'),
                $this->qty('minimum_order_quantity', 'MOQ', false), $this->col('lead_time_days', 'Lead Time', sort: 'lead_time_days'), $this->col('is_preferred', 'Preferred', format: 'boolean', sort: 'is_preferred'),
            ], ['supplier.name', 'item.code', 'item.name', 'supplier_item_code', 'supplier_item_name'], ['supplier', 'item', 'defaultPurchaseUom'], includeGlobalOrganization: true),

            $this->serviceJob('vehicle-service.jobs', 'Vehicle Service Jobs'),
            $this->profitabilityReport(),
            $this->definition('vehicle-service.job-status', 'Job Status', 'Vehicle Service', VehicleServiceStatusHistory::class, [
                $this->col('changed_at', 'Changed At', format: 'datetime', sort: 'changed_at'), $this->col('job', 'Job', 'job.job_number'),
                $this->col('old_status', 'From', format: 'enum', sort: 'old_status'), $this->col('new_status', 'To', format: 'enum', sort: 'new_status'),
            ], ['job.job_number', 'old_status', 'new_status'], ['job'], 'changed_at'),
            $this->labour('vehicle-service.labour-assignment', 'Labour Assignment'),
            $this->labour('vehicle-service.technician-work', 'Technician Work'),
            $this->labour('vehicle-service.employee-commissions', 'Employee Commission Report'),
            $this->definition('vehicle-service.supervisor-commission', 'Supervisor Commission', 'Vehicle Service', VehicleServiceJob::class, [
                $this->col('job_date', 'Date', format: 'date', sort: 'job_date'), $this->col('job_number', 'Job', sort: 'job_number'),
                $this->col('supervisor', 'Supervisor', 'supervisor.display_name'), $this->money('supervisor_commission_value', 'Value', false),
                $this->money('supervisor_commission_amount', 'Commission'), $this->money('grand_total', 'Job Total'), $this->col('status', 'Status', format: 'enum', sort: 'status'),
            ], ['job_number', 'supervisor.display_name'], ['supervisor'], 'job_date'),
            $this->definition('vehicle-service.parts-usage', 'Parts Usage', 'Vehicle Service', VehicleServiceJobLine::class, [
                $this->col('job', 'Job', 'job.job_number'), $this->itemCol(), $this->qty('quantity', 'Quantity'), $this->money('unit_cost', 'Unit Cost', false), $this->money('line_total', 'Line Total'), $this->col('status', 'Status', format: 'enum', sort: 'status'),
            ], ['job.job_number', 'item.code', 'item.name'], ['job', 'item'], constraints: ['is_inventory_tracked' => true]),

            ...$this->rentalReports(),

            $this->invoice('invoice.register', 'Invoice Register', Invoice::class, 'invoice_date', 'invoice_number', 'grand_total'),
            $this->definition('invoice.balance', 'Invoice Balance', 'Invoice & Payment', InvoiceBalance::class, [
                $this->col('invoice', 'Invoice', 'invoice.invoice_number'), $this->money('invoice_total', 'Invoice Total'), $this->money('paid_amount', 'Paid'),
                $this->money('credit_allocated_amount', 'Credits'), $this->money('remaining_amount', 'Remaining'), $this->col('status', 'Status', format: 'enum', sort: 'status'),
            ], ['invoice.invoice_number'], ['invoice']),
            $this->payment('payment.register', 'Payment Register', Payment::class, 'payment_date', 'payment_number', 'total_amount'),
            $this->definition('payment.allocation', 'Payment Allocation', 'Invoice & Payment', PaymentAllocation::class, [
                $this->col('allocation_date', 'Date', format: 'date', sort: 'allocation_date'), $this->col('payment', 'Payment', 'payment.payment_number'),
                $this->col('invoice', 'Invoice', 'invoice.invoice_number'), $this->money('invoice_total', 'Invoice Total'), $this->money('allocated_amount', 'Allocated'),
                $this->money('invoice_balance_after', 'Balance After'), $this->col('allocation_method', 'Method', format: 'enum', sort: 'allocation_method'), $this->col('status', 'Status', format: 'enum', sort: 'status'),
            ], ['payment.payment_number', 'invoice.invoice_number'], ['payment', 'invoice'], 'allocation_date'),
            $this->definition('payment.advance', 'Advance Payment Report', 'Invoice & Payment', Payment::class, [
                $this->col('payment_date', 'Date', format: 'date', sort: 'payment_date'), $this->col('payment_number', 'Payment', sort: 'payment_number'),
                $this->col('party_type', 'Party Type', format: 'enum', sort: 'party_type'), $this->col('party_id', 'Party ID', sort: 'party_id'),
                $this->col('source_type', 'Source', format: 'enum', sort: 'source_type'), $this->col('source_id', 'Source ID', sort: 'source_id'),
                $this->money('total_amount', 'Amount'), $this->money('allocated_amount', 'Allocated'), $this->money('unapplied_amount', 'Unapplied'),
                $this->col('allocation_status', 'Allocation Status', format: 'enum', sort: 'allocation_status'), $this->col('status', 'Status', format: 'enum', sort: 'status'),
            ], ['payment_number', 'party_type', 'source_type'], [], 'payment_date', constraints: ['payment_type' => 'advance']),
            $this->definition('payment.unapplied', 'Unapplied Balance Report', 'Invoice & Payment', PaymentUnappliedBalance::class, [
                $this->col('payment', 'Payment', 'payment.payment_number'), $this->money('original_amount', 'Original'), $this->money('allocated_amount', 'Allocated'),
                $this->money('refunded_amount', 'Refunded'), $this->money('remaining_amount', 'Remaining'), $this->col('balance_type', 'Type', format: 'enum', sort: 'balance_type'),
                $this->col('party_type', 'Party Type', format: 'enum', sort: 'party_type'), $this->col('party_id', 'Party ID', sort: 'party_id'), $this->col('status', 'Status', format: 'enum', sort: 'status'),
            ], ['payment.payment_number', 'party_type'], ['payment']),
            $this->creditReport('payment.customer-credit', 'Customer Credit Report', 'customer'),
            $this->creditReport('payment.supplier-credit', 'Supplier Credit Report', 'supplier'),
            $this->definition('payment.refund', 'Refund Report', 'Invoice & Payment', PaymentRefund::class, [
                $this->col('refund_date', 'Date', format: 'date', sort: 'refund_date'), $this->col('refund_number', 'Refund', sort: 'refund_number'),
                $this->col('payment', 'Payment', 'payment.payment_number'), $this->col('party_type', 'Party Type', format: 'enum', sort: 'party_type'),
                $this->col('party_id', 'Party ID', sort: 'party_id'), $this->money('amount', 'Amount'), $this->col('status', 'Status', format: 'enum', sort: 'status'),
            ], ['refund_number', 'payment.payment_number', 'party_type'], ['payment'], 'refund_date'),
            $this->definition('payment.reversal', 'Payment Reversal Report', 'Invoice & Payment', PaymentReversal::class, [
                $this->col('reversal_date', 'Date', format: 'date', sort: 'reversal_date'), $this->col('reversal_number', 'Reversal', sort: 'reversal_number'),
                $this->col('payment', 'Payment', 'payment.payment_number'), $this->money('original_amount', 'Original'), $this->money('reversed_amount', 'Reversed'),
                $this->col('status', 'Status', format: 'enum', sort: 'status'),
            ], ['reversal_number', 'payment.payment_number'], ['payment'], 'reversal_date'),
            $this->paymentLineReport('payment.cheque-status', 'Cheque Status Report', ['cheque']),
            $this->paymentLineReport('payment.bank-transfer', 'Bank Transfer Report', ['bank_transfer', 'bank', 'transfer']),
            $this->paymentLineReport('payment.cash-collection', 'Cash Collection Report', ['cash'], 'inbound'),

            $this->definition('finance.account-balances', 'Account Balances', 'Finance', FinanceAccountBalance::class, [
                $this->col('account', 'Account', 'account.code'), $this->col('account_name', 'Account Name', 'account.name'), $this->money('opening_debit', 'Opening Dr'),
                $this->money('opening_credit', 'Opening Cr'), $this->money('period_debit', 'Period Dr'), $this->money('period_credit', 'Period Cr'),
                $this->money('closing_debit', 'Closing Dr'), $this->money('closing_credit', 'Closing Cr'),
            ], ['account.code', 'account.name'], ['account', 'fiscalPeriod']),
            $this->chartOfAccounts(),
            $this->definition('finance.journals', 'Journal Entries', 'Finance', FinanceJournalEntry::class, [
                $this->col('journal_date', 'Date', format: 'date', sort: 'journal_date'), $this->col('journal_number', 'Journal', sort: 'journal_number'),
                $this->col('journal_type', 'Type', format: 'enum', sort: 'journal_type'), $this->col('source_module', 'Source Module', sort: 'source_module'),
                $this->col('source_number', 'Source Number', sort: 'source_number'), $this->money('total_debit', 'Debit'), $this->money('total_credit', 'Credit'), $this->col('status', 'Status', format: 'enum', sort: 'status'),
            ], ['journal_number', 'description'], [], 'journal_date'),
            $this->definition('finance.ledger', 'Ledger', 'Finance', FinanceLedgerEntry::class, [
                $this->col('entry_date', 'Date', format: 'date', sort: 'entry_date'), $this->col('account', 'Account', 'account.code'), $this->col('account_name', 'Account Name', 'account.name'),
                $this->col('journal', 'Journal', 'journalEntry.journal_number'), $this->col('source_module', 'Source Module', sort: 'source_module'),
                $this->col('source_number', 'Source Number', sort: 'source_number'), $this->money('debit', 'Debit'), $this->money('credit', 'Credit'), $this->money('balance_after', 'Balance'),
            ], ['account.code', 'account.name', 'journalEntry.journal_number'], ['account', 'journalEntry'], 'entry_date'),
            $this->definition('finance.trial-balance', 'Trial Balance', 'Finance', FinanceAccountBalance::class, [
                $this->col('account', 'Account', 'account.code'), $this->col('account_name', 'Account Name', 'account.name'), $this->money('closing_debit', 'Debit'), $this->money('closing_credit', 'Credit'),
            ], ['account.code', 'account.name'], ['account']),
            $this->cashFlowReport(),
            $this->aging('finance.ar-aging', 'Accounts Receivable Aging', 'outbound'),
            $this->aging('finance.ap-aging', 'Accounts Payable Aging', 'inbound'),
            $this->definition('finance.bank-reconciliation', 'Bank Reconciliation Report', 'Finance', FinanceBankReconciliation::class, [
                $this->col('statement_date', 'Statement Date', format: 'date', sort: 'statement_date'),
                $this->col('statement_reference', 'Statement', sort: 'statement_reference'),
                $this->col('bank_account', 'Bank Account', 'bankAccount.code'),
                $this->money('opening_balance', 'Opening'),
                $this->money('closing_balance', 'Closing'),
                $this->money('reconciled_balance', 'Reconciled'),
                $this->col('status', 'Status', sort: 'status'),
            ], ['statement_reference', 'bankAccount.code', 'bankAccount.name'], ['bankAccount'], 'statement_date'),
            $this->definition('finance.budget-vs-actual', 'Actual vs Budget', 'Finance', FinanceBudget::class, [
                $this->col('budget_year', 'Year', sort: 'budget_year'),
                $this->col('name', 'Budget', sort: 'name'),
                $this->col('status', 'Status', sort: 'status'),
                new ReportColumn(
                    key: 'line_count',
                    label: 'Lines',
                    value: static fn (FinanceBudget $budget): int => $budget->lines->count(),
                ),
            ], ['name'], ['lines'], null),

            $this->taxTransactions('tax.transactions', 'Tax Transactions'),
            $this->taxTransactions('tax.summary', 'Tax Summary'),
            $this->taxTransactions('tax.liability', 'Tax Liability', ['payable' => true]),
            $this->taxTransactions('finance.tax-liability', 'Tax Liability Report', ['payable' => true]),
            $this->taxTransactions('finance.tax-reconciliation', 'Tax Reconciliation Report'),
            $this->taxTransactions('tax.receivable', 'Tax Receivable', ['receivable' => true]),
            $this->taxTransactions('tax.vat', 'VAT Report', ['tax_type' => 'VAT']),
            $this->taxTransactions('tax.wht', 'WHT Report', ['is_withholding' => true]),
        ];

        return collect($reports)->keyBy->key->all();
    }

    /**
     * @param  class-string  $model
     * @param  array<int, ReportColumn>  $columns
     * @param  array<int, string>  $search
     * @param  array<int, string>  $relations
     * @param  array<string, mixed>  $constraints
     */
    private function definition(string $key, string $title, string $group, string $model, array $columns, array $search = [], array $relations = [], ?string $dateColumn = null, bool $includeGlobalOrganization = false, array $constraints = []): ReportDefinition
    {
        return new ReportDefinition(
            key: $key,
            title: $title,
            group: $group,
            model: $model,
            columns: $columns,
            search: $search,
            relations: $relations,
            filters: [$this->filter('status', 'Status', 'status')],
            dateColumn: $dateColumn,
            defaultSort: $dateColumn !== null && ! str_contains($dateColumn, '.') ? $dateColumn : 'id',
            includeGlobalOrganization: $includeGlobalOrganization,
            constraints: $constraints,
        );
    }

    private function masters(string $key, string $title, string $model, array $columns, array $search, array $relations = [], ?string $dateColumn = null): ReportDefinition
    {
        return $this->definition($key, $title, 'Masters', $model, $columns, $search, $relations, $dateColumn, true);
    }

    /**
     * @return list<ReportDefinition>
     */
    private function rentalReports(): array
    {
        $partyName = static fn (RentalAgreement $agreement): ?string => $agreement->party_type->value === 'customer'
            ? ($agreement->customer?->display_name ?? $agreement->customer?->name)
            : ($agreement->supplier?->display_name ?? $agreement->supplier?->name);
        $vehicleLabel = static fn (RentalAgreement $agreement): ?string => $agreement->vehicles
            ->first()?->vehicle?->registration_number
            ?? $agreement->vehicles->first()?->vehicle?->vehicle_number;

        return [
            new ReportDefinition(
                key: 'vehicle-rental.fleet-availability',
                title: 'Fleet Availability Report',
                group: 'Vehicle Rental',
                model: Vehicle::class,
                columns: [
                    $this->col('vehicle_number', 'Vehicle', sort: 'vehicle_number'),
                    $this->col('registration_number', 'Registration', sort: 'registration_number'),
                    $this->col('make', 'Make', 'make.name'),
                    $this->col('model', 'Model', 'model.name'),
                    $this->col('status', 'Fleet Status', format: 'enum', sort: 'status'),
                    new ReportColumn(
                        key: 'availability',
                        label: 'Availability',
                        value: static fn (Vehicle $vehicle): string => $vehicle->status->value === 'active' ? 'available' : 'unavailable',
                    ),
                ],
                search: ['vehicle_number', 'registration_number', 'make.name', 'model.name'],
                relations: ['make', 'model'],
                filters: [$this->filter('status', 'Status', 'status')],
                defaultSort: 'vehicle_number',
                defaultDirection: 'asc',
                includeGlobalOrganization: true,
                description: 'Current fleet master availability; date-range availability remains in the rental availability workspace.',
            ),
            $this->rentalAgreementReport('vehicle-rental.agreement-register', 'Rental Agreement Register'),
            new ReportDefinition(
                key: 'vehicle-rental.active-rentals',
                title: 'Active Rentals Report',
                group: 'Vehicle Rental',
                model: RentalAgreement::class,
                columns: $this->rentalAgreementColumns($partyName, $vehicleLabel),
                search: ['agreement_number', 'customer.name', 'supplier.name', 'vehicles.vehicle.registration_number'],
                relations: ['customer', 'supplier', 'vehicles.vehicle'],
                filters: [$this->filter('direction', 'Direction', 'direction')],
                dateColumn: 'start_at',
                defaultSort: 'start_at',
                constraints: ['status' => 'active'],
            ),
            new ReportDefinition(
                key: 'vehicle-rental.overdue-rentals',
                title: 'Overdue Rentals Report',
                group: 'Vehicle Rental',
                model: RentalAgreement::class,
                columns: [
                    ...$this->rentalAgreementColumns($partyName, $vehicleLabel),
                    new ReportColumn(
                        key: 'days_overdue',
                        label: 'Days Overdue',
                        value: static fn (RentalAgreement $agreement): int => max(
                            0,
                            (int) $agreement->expected_end_at->startOfDay()->diffInDays(now()->startOfDay()),
                        ),
                    ),
                ],
                search: ['agreement_number', 'customer.name', 'supplier.name', 'vehicles.vehicle.registration_number'],
                relations: ['customer', 'supplier', 'vehicles.vehicle'],
                filters: [$this->filter('direction', 'Direction', 'direction')],
                dateColumn: 'expected_end_at',
                defaultSort: 'expected_end_at',
                scope: static fn ($query) => $query
                    ->whereIn('status', ['confirmed', 'active'])
                    ->where('expected_end_at', '<', now()),
            ),
            $this->definition('vehicle-rental.running-chart', 'Running Chart Report', 'Vehicle Rental', RentalUsageLog::class, [
                $this->col('usage_date', 'Date', format: 'date', sort: 'usage_date'),
                $this->col('agreement', 'Agreement', 'agreement.agreement_number'),
                $this->col('vehicle', 'Vehicle', 'vehicle.registration_number'),
                $this->col('driver', 'Driver', 'driver.display_name'),
                $this->qty('start_odometer', 'Start KM', false),
                $this->qty('end_odometer', 'Finish KM', false),
                $this->qty('distance_km', 'Distance'),
                $this->qty('cumulative_km', 'Cumulative'),
                $this->col('start_time', 'ON Time', sort: 'start_time'),
                $this->col('end_time', 'OFF Time', sort: 'end_time'),
                $this->col('status', 'Status', format: 'enum', sort: 'status'),
            ], ['agreement.agreement_number', 'vehicle.registration_number', 'driver.display_name', 'trip_from', 'trip_to'], ['agreement', 'vehicle', 'driver'], 'usage_date'),
            $this->definition('vehicle-rental.running-chart-customer', 'Running Chart for Customer / Lessee', 'Vehicle Rental', RentalUsageContext::class, [
                $this->col('usage_date', 'Date', 'usageLog.usage_date', 'date'),
                $this->col('agreement', 'Customer Agreement', 'agreement.agreement_number'),
                $this->col('customer', 'Customer / Lessee', 'agreement.customer.display_name'),
                $this->col('vehicle', 'Vehicle', 'usageLog.vehicle.registration_number'),
                new ReportColumn('start_odometer', 'Start KM', 'usageLog.start_odometer', format: 'decimal'),
                new ReportColumn('end_odometer', 'Finish KM', 'usageLog.end_odometer', format: 'decimal'),
                new ReportColumn('distance_km', 'Distance', 'usageLog.distance_km', format: 'decimal', summarize: true),
                $this->col('route_from', 'From', 'usageLog.trip_from'),
                $this->col('route_to', 'To', 'usageLog.trip_to'),
                $this->col('status', 'Status', 'usageLog.status', 'enum'),
            ], ['agreement.agreement_number', 'agreement.customer.name', 'usageLog.vehicle.registration_number'], ['agreement.customer', 'usageLog.vehicle'], 'usageLog.usage_date', constraints: ['financial_side' => 'revenue']),
            $this->definition('vehicle-rental.running-chart-owner', 'Running Chart for Vehicle Owner', 'Vehicle Rental', RentalUsageContext::class, [
                $this->col('usage_date', 'Date', 'usageLog.usage_date', 'date'),
                $this->col('agreement', 'Owner Agreement', 'agreement.agreement_number'),
                $this->col('owner', 'Owner / Supplier', 'agreement.supplier.display_name'),
                $this->col('vehicle', 'Vehicle', 'usageLog.vehicle.registration_number'),
                $this->col('lessee', 'Lessee Agreement', 'agreementVehicleLink.outboundAgreement.agreement_number'),
                new ReportColumn('start_odometer', 'Start KM', 'usageLog.start_odometer', format: 'decimal'),
                new ReportColumn('end_odometer', 'Finish KM', 'usageLog.end_odometer', format: 'decimal'),
                new ReportColumn('distance_km', 'Distance', 'usageLog.distance_km', format: 'decimal', summarize: true),
                $this->col('status', 'Status', 'usageLog.status', 'enum'),
            ], ['agreement.agreement_number', 'agreement.supplier.name', 'usageLog.vehicle.registration_number', 'agreementVehicleLink.outboundAgreement.agreement_number'], ['agreement.supplier', 'usageLog.vehicle', 'agreementVehicleLink.outboundAgreement'], 'usageLog.usage_date', constraints: ['financial_side' => 'cost']),
            $this->definition('vehicle-rental.usage-summary', 'Usage Summary Report', 'Vehicle Rental', RentalUsageLog::class, [
                $this->col('usage_date', 'Date', format: 'date', sort: 'usage_date'),
                $this->col('agreement', 'Agreement', 'agreement.agreement_number'),
                $this->col('vehicle', 'Vehicle', 'vehicle.registration_number'),
                $this->qty('distance_km', 'Distance'),
                $this->qty('comparative_km', 'Comparative KM'),
                new ReportColumn(
                    key: 'event_total',
                    label: 'Classified Event Quantity',
                    format: 'decimal',
                    summarize: true,
                    value: fn (RentalUsageLog $log): string => $this->math->sum(
                        $log->events->pluck('quantity')->map(fn ($value) => (string) $value)->all(),
                    ),
                ),
                $this->col('status', 'Status', format: 'enum', sort: 'status'),
            ], ['agreement.agreement_number', 'vehicle.registration_number'], ['agreement', 'vehicle', 'events'], 'usage_date'),
            $this->definition('vehicle-rental.running-chart-monthly', 'Running Chart Monthly Summary', 'Vehicle Rental', RentalUsageLog::class, [
                new ReportColumn(
                    key: 'month',
                    label: 'Month',
                    value: static fn (RentalUsageLog $log): string => $log->usage_date->format('Y-m'),
                ),
                $this->col('vehicle', 'Vehicle', 'vehicle.registration_number'),
                $this->col('agreement', 'Source Agreement', 'agreement.agreement_number'),
                $this->qty('distance_km', 'Distance'),
                new ReportColumn(
                    key: 'working_hours',
                    label: 'Working Hours',
                    format: 'decimal',
                    summarize: true,
                    value: fn (RentalUsageLog $log): string => $this->math->div(
                        (string) $log->working_minutes,
                        '60.000000',
                    ),
                ),
                $this->qty('cumulative_km', 'Approved Cumulative KM'),
            ], ['agreement.agreement_number', 'vehicle.registration_number'], ['agreement', 'vehicle'], 'usage_date', constraints: ['status' => 'approved']),
            $this->definition('vehicle-rental.allocation-history', 'Vehicle Allocation History', 'Vehicle Rental', RentalAgreementVehicle::class, [
                $this->col('vehicle', 'Vehicle', 'vehicle.registration_number'),
                $this->col('agreement', 'Agreement', 'agreement.agreement_number'),
                $this->col('direction', 'Direction', 'agreement.direction', 'enum'),
                $this->col('allocated_from', 'From', format: 'datetime', sort: 'allocated_from'),
                $this->col('allocated_to', 'To', format: 'datetime', sort: 'allocated_to'),
                $this->qty('start_odometer', 'Start KM', false),
                $this->qty('end_odometer', 'End KM', false),
                $this->col('status', 'Status', format: 'enum', sort: 'status'),
            ], ['vehicle.registration_number', 'agreement.agreement_number'], ['vehicle', 'agreement'], 'allocated_from'),
            $this->definition('vehicle-rental.mileage-summary', 'Rental Mileage Summary', 'Vehicle Rental', RentalUsageLog::class, [
                $this->col('usage_date', 'Date', format: 'date', sort: 'usage_date'),
                $this->col('vehicle', 'Vehicle', 'vehicle.registration_number'),
                $this->col('agreement', 'Source Agreement', 'agreement.agreement_number'),
                $this->qty('start_odometer', 'Start KM', false),
                $this->qty('end_odometer', 'Finish KM', false),
                $this->qty('distance_km', 'Distance'),
                $this->qty('comparative_km', 'Comparative KM'),
                $this->qty('cumulative_km', 'Approved Cumulative KM'),
            ], ['vehicle.registration_number', 'agreement.agreement_number'], ['vehicle', 'agreement'], 'usage_date', constraints: ['status' => 'approved']),
            new ReportDefinition(
                key: 'vehicle-rental.overtime-summary',
                title: 'Rental Overtime Summary',
                group: 'Vehicle Rental',
                model: RentalUsageEvent::class,
                columns: [
                    $this->col('usage_date', 'Date', 'usageLog.usage_date', 'date'),
                    $this->col('vehicle', 'Vehicle', 'usageLog.vehicle.registration_number'),
                    $this->col('event_type', 'Overtime Type', format: 'enum', sort: 'event_type'),
                    $this->qty('quantity', 'Hours'),
                    $this->col('remarks', 'Remarks'),
                ],
                search: ['usageLog.vehicle.registration_number', 'remarks'],
                relations: ['usageLog.vehicle'],
                filters: [$this->filter('event_type', 'Overtime Type', 'event_type')],
                dateColumn: 'usageLog.usage_date',
                defaultSort: 'id',
                scope: static fn ($query) => $query->whereIn('event_type', ['overtime', 'double_overtime']),
            ),
            new ReportDefinition(
                key: 'vehicle-rental.day-night-out-summary',
                title: 'Rental Day Out / Night Out Summary',
                group: 'Vehicle Rental',
                model: RentalUsageEvent::class,
                columns: [
                    $this->col('usage_date', 'Date', 'usageLog.usage_date', 'date'),
                    $this->col('vehicle', 'Vehicle', 'usageLog.vehicle.registration_number'),
                    $this->col('event_type', 'Event', format: 'enum', sort: 'event_type'),
                    $this->qty('quantity', 'Quantity'),
                    $this->col('remarks', 'Remarks'),
                ],
                search: ['usageLog.vehicle.registration_number', 'remarks'],
                relations: ['usageLog.vehicle'],
                filters: [$this->filter('event_type', 'Event', 'event_type')],
                dateColumn: 'usageLog.usage_date',
                defaultSort: 'id',
                scope: static fn ($query) => $query->whereIn('event_type', ['day_out', 'night_out']),
            ),
            $this->definition('vehicle-rental.charges', 'Rental Charge Report', 'Vehicle Rental', RentalCharge::class, [
                $this->col('agreement', 'Agreement', 'agreement.agreement_number'),
                $this->col('charge_type', 'Charge Type', format: 'enum', sort: 'charge_type'),
                $this->col('description', 'Description', sort: 'description'),
                $this->qty('quantity', 'Quantity'),
                $this->money('rate', 'Rate', false),
                $this->money('tax_amount', 'Tax'),
                $this->money('total_amount', 'Total'),
                $this->col('invoice_status', 'Invoice Status', format: 'enum', sort: 'invoice_status'),
                $this->col('status', 'Status', format: 'enum', sort: 'status'),
            ], ['agreement.agreement_number', 'description', 'charge_type'], ['agreement'], 'created_at'),
            $this->rentalInvoiceReport('vehicle-rental.revenue', 'Rental Revenue Report', 'outbound'),
            $this->rentalInvoiceReport('vehicle-rental.inbound-cost', 'Inbound Rental Cost Report', 'inbound'),
            new ReportDefinition(
                key: 'vehicle-rental.profitability',
                title: 'Rental Profitability Report',
                group: 'Vehicle Rental',
                model: RentalAgreementVehicleLink::class,
                columns: [
                    $this->col('effective_from', 'From', format: 'datetime', sort: 'effective_from'),
                    $this->col('effective_to', 'To', format: 'datetime', sort: 'effective_to'),
                    $this->col('vehicle', 'Vehicle', 'vehicle.registration_number'),
                    $this->col('outbound_agreement', 'Customer Agreement', 'outboundAgreement.agreement_number'),
                    $this->col('inbound_agreement', 'Owner Agreement', 'inboundAgreement.agreement_number'),
                    new ReportColumn(
                        key: 'revenue',
                        label: 'Revenue',
                        format: 'money',
                        summarize: true,
                        value: fn (RentalAgreementVehicleLink $link): string => $this->approvedRentalChargeTotal(
                            $link->outboundAgreement,
                        ),
                    ),
                    new ReportColumn(
                        key: 'cost',
                        label: 'Direct Cost',
                        format: 'money',
                        summarize: true,
                        value: fn (RentalAgreementVehicleLink $link): string => $this->math->add(
                            $this->approvedRentalChargeTotal($link->inboundAgreement),
                            $this->companyBorneRentalExpenseTotal($link->outboundAgreement),
                        ),
                    ),
                    new ReportColumn(
                        key: 'profit',
                        label: 'Profit',
                        format: 'money',
                        summarize: true,
                        value: fn (RentalAgreementVehicleLink $link): string => $this->math->sub(
                            $this->approvedRentalChargeTotal($link->outboundAgreement),
                            $this->math->add(
                                $this->approvedRentalChargeTotal($link->inboundAgreement),
                                $this->companyBorneRentalExpenseTotal($link->outboundAgreement),
                            ),
                        ),
                    ),
                    $this->col('status', 'Status', format: 'enum', sort: 'status'),
                ],
                search: ['outboundAgreement.agreement_number', 'inboundAgreement.agreement_number', 'vehicle.registration_number'],
                relations: ['vehicle', 'outboundAgreement.charges', 'outboundAgreement.expenses', 'inboundAgreement.charges'],
                filters: [$this->filter('status', 'Status', 'status')],
                dateColumn: 'effective_from',
                defaultSort: 'effective_from',
                orientation: 'landscape',
            ),
            new ReportDefinition(
                key: 'vehicle-rental.deposit-liability',
                title: 'Deposit Liability Report',
                group: 'Vehicle Rental',
                model: RentalPaymentLink::class,
                columns: [
                    $this->col('payment_date', 'Date', 'payment.payment_date', 'date'),
                    $this->col('agreement', 'Agreement', 'agreement.agreement_number'),
                    $this->col('payment', 'Payment', 'payment.payment_number'),
                    $this->money('amount', 'Deposit'),
                    $this->col('status', 'Status', sort: 'status'),
                ],
                search: ['agreement.agreement_number', 'payment.payment_number'],
                relations: ['agreement', 'payment'],
                dateColumn: 'payment.payment_date',
                defaultSort: 'id',
                constraints: ['link_type' => 'deposit'],
            ),
            $this->rentalOutstandingReport('vehicle-rental.customer-outstanding', 'Customer Outstanding Rental Report', 'outbound'),
            $this->rentalOutstandingReport('vehicle-rental.owner-payable', 'Owner/Supplier Payable Rental Report', 'inbound'),
            $this->rentalOutstandingReport('vehicle-rental.customer-ageing', 'Customer Rental Outstanding Ageing', 'outbound'),
            $this->rentalOutstandingReport('vehicle-rental.owner-payable-ageing', 'Owner/Supplier Payable Ageing', 'inbound'),
            new ReportDefinition(
                key: 'vehicle-rental.uninvoiced-revenue',
                title: 'Uninvoiced Approved Rental Revenue',
                group: 'Vehicle Rental',
                model: RentalCharge::class,
                columns: [
                    $this->col('agreement', 'Customer Agreement', 'agreement.agreement_number'),
                    $this->col('charge_type', 'Charge Type', format: 'enum', sort: 'charge_type'),
                    $this->qty('quantity', 'Approved Quantity'),
                    $this->money('total_amount', 'Approved Amount'),
                    $this->col('invoice_status', 'Invoice Status', format: 'enum', sort: 'invoice_status'),
                ],
                search: ['agreement.agreement_number', 'description'],
                relations: ['agreement'],
                filters: [$this->filter('invoice_status', 'Invoice Status', 'invoice_status')],
                dateColumn: 'created_at',
                defaultSort: 'created_at',
                scope: static fn ($query) => $query
                    ->where('financial_side', 'revenue')
                    ->where('status', 'approved')
                    ->where('invoice_status', '!=', 'invoiced'),
            ),
            new ReportDefinition(
                key: 'vehicle-rental.unprocessed-payable-cost',
                title: 'Unprocessed Owner / Supplier Rental Cost',
                group: 'Vehicle Rental',
                model: RentalCharge::class,
                columns: [
                    $this->col('agreement', 'Owner Agreement', 'agreement.agreement_number'),
                    $this->col('charge_type', 'Cost Type', format: 'enum', sort: 'charge_type'),
                    $this->qty('quantity', 'Approved Quantity'),
                    $this->money('total_amount', 'Approved Cost'),
                    $this->col('invoice_status', 'Payable Status', format: 'enum', sort: 'invoice_status'),
                ],
                search: ['agreement.agreement_number', 'description'],
                relations: ['agreement'],
                filters: [$this->filter('invoice_status', 'Payable Status', 'invoice_status')],
                dateColumn: 'created_at',
                defaultSort: 'created_at',
                scope: static fn ($query) => $query
                    ->where('financial_side', 'cost')
                    ->where('status', 'approved')
                    ->where('invoice_status', '!=', 'invoiced'),
            ),
            $this->definition('vehicle-rental.partially-invoiced', 'Partially Invoiced Rental Charges', 'Vehicle Rental', RentalCharge::class, [
                $this->col('agreement', 'Agreement', 'agreement.agreement_number'),
                $this->col('financial_side', 'Side', format: 'enum', sort: 'financial_side'),
                $this->col('charge_type', 'Charge Type', format: 'enum', sort: 'charge_type'),
                $this->qty('quantity', 'Quantity'),
                $this->money('total_amount', 'Amount'),
                $this->col('invoice_status', 'Status', format: 'enum', sort: 'invoice_status'),
            ], ['agreement.agreement_number', 'description'], ['agreement'], 'created_at', constraints: ['invoice_status' => 'partially_invoiced']),
            $this->definition('vehicle-rental.expense-recovery', 'Rental Expense and Recovery Report', 'Vehicle Rental', RentalExpense::class, [
                $this->col('expense_date', 'Date', format: 'date', sort: 'expense_date'),
                $this->col('agreement', 'Agreement', 'agreement.agreement_number'),
                $this->col('expense_type', 'Expense Type', format: 'enum', sort: 'expense_type'),
                $this->col('financial_treatment', 'Financial Treatment', format: 'enum', sort: 'financial_treatment'),
                $this->money('amount', 'Amount'),
                $this->money('tax_amount', 'Tax'),
                $this->col('charge_generation_status', 'Charge Status', format: 'enum', sort: 'charge_generation_status'),
                $this->col('status', 'Approval Status', format: 'enum', sort: 'status'),
            ], ['agreement.agreement_number', 'receipt_no', 'reference_no', 'description'], ['agreement'], 'expense_date'),
            $this->definition('vehicle-rental.payment-allocation', 'Rental Payment and Receipt Allocation Report', 'Vehicle Rental', RentalPaymentLink::class, [
                $this->col('payment_date', 'Date', 'payment.payment_date', 'date'),
                $this->col('agreement', 'Agreement', 'agreement.agreement_number'),
                $this->col('direction', 'Payment Direction', 'payment.direction', 'enum'),
                $this->col('link_type', 'Purpose', format: 'enum', sort: 'link_type'),
                $this->col('payment', 'Payment', 'payment.payment_number'),
                $this->col('invoice', 'Invoice / Payable', 'invoice.invoice_number'),
                $this->money('amount', 'Linked Amount'),
                $this->col('status', 'Status', sort: 'status'),
            ], ['agreement.agreement_number', 'payment.payment_number', 'invoice.invoice_number'], ['agreement', 'payment', 'invoice'], 'payment.payment_date'),
            new ReportDefinition(
                key: 'vehicle-rental.deposit-refund',
                title: 'Rental Deposits, Advances, and Refunds',
                group: 'Vehicle Rental',
                model: RentalPaymentLink::class,
                columns: [
                    $this->col('payment_date', 'Date', 'payment.payment_date', 'date'),
                    $this->col('agreement', 'Agreement', 'agreement.agreement_number'),
                    $this->col('link_type', 'Type', format: 'enum', sort: 'link_type'),
                    $this->col('payment', 'Payment', 'payment.payment_number'),
                    $this->money('amount', 'Amount'),
                    $this->col('status', 'Status', sort: 'status'),
                ],
                search: ['agreement.agreement_number', 'payment.payment_number'],
                relations: ['agreement', 'payment'],
                filters: [$this->filter('link_type', 'Type', 'link_type')],
                dateColumn: 'payment.payment_date',
                defaultSort: 'id',
                scope: static fn ($query) => $query->whereIn('link_type', ['deposit', 'advance', 'refund']),
            ),
            new ReportDefinition(
                key: 'vehicle-rental.vehicle-utilization',
                title: 'Vehicle Utilization Report',
                group: 'Vehicle Rental',
                model: RentalAgreementVehicle::class,
                columns: [
                    $this->col('vehicle', 'Vehicle', 'vehicle.registration_number'),
                    $this->col('agreement', 'Agreement', 'agreement.agreement_number'),
                    $this->col('allocated_from', 'From', format: 'datetime', sort: 'allocated_from'),
                    $this->col('allocated_to', 'To', format: 'datetime', sort: 'allocated_to'),
                    new ReportColumn(
                        key: 'utilized_hours',
                        label: 'Utilized Hours',
                        format: 'decimal',
                        summarize: true,
                        value: fn (RentalAgreementVehicle $allocation): string => $this->math->div(
                            (string) max(
                                0,
                                ($allocation->allocated_to ?? now())->getTimestamp()
                                    - $allocation->allocated_from->getTimestamp(),
                            ),
                            '3600.000000',
                        ),
                    ),
                    $this->qty('start_odometer', 'Start KM', false),
                    $this->qty('end_odometer', 'End KM', false),
                    $this->col('status', 'Status', format: 'enum', sort: 'status'),
                ],
                search: ['vehicle.registration_number', 'vehicle.vehicle_number', 'agreement.agreement_number'],
                relations: ['vehicle', 'agreement'],
                filters: [$this->filter('status', 'Status', 'status')],
                dateColumn: 'allocated_from',
                defaultSort: 'allocated_from',
                orientation: 'landscape',
            ),
            new ReportDefinition(
                key: 'vehicle-rental.revenue-licence-expiry',
                title: 'Vehicle Revenue Licence Expiry Report',
                group: 'Vehicle Rental',
                model: VehicleDocument::class,
                columns: [
                    $this->col('vehicle', 'Vehicle', 'vehicle.registration_number'),
                    $this->col('document_number', 'Licence Number', sort: 'document_number'),
                    $this->col('issued_date', 'Issued', format: 'date', sort: 'issued_date'),
                    $this->col('expiry_date', 'Expiry', format: 'date', sort: 'expiry_date'),
                    new ReportColumn(
                        key: 'days_to_expiry',
                        label: 'Days To Expiry',
                        value: static fn (VehicleDocument $document): ?int => $document->expiry_date?->startOfDay()
                            ->diffInDays(now()->startOfDay(), false) * -1,
                    ),
                    $this->col('status', 'Status', format: 'enum', sort: 'status'),
                ],
                search: ['vehicle.registration_number', 'vehicle.vehicle_number', 'document_number'],
                relations: ['vehicle'],
                filters: [$this->filter('status', 'Status', 'status')],
                dateColumn: 'expiry_date',
                defaultSort: 'expiry_date',
                constraints: ['document_type' => 'revenue_license'],
            ),
            new ReportDefinition(
                key: 'vehicle-rental.document-expiry',
                title: 'Vehicle Document Expiry Report',
                group: 'Vehicle Rental',
                model: VehicleDocument::class,
                columns: [
                    $this->col('vehicle', 'Vehicle', 'vehicle.registration_number'),
                    $this->col('document_type', 'Document Type', format: 'enum', sort: 'document_type'),
                    $this->col('document_number', 'Document Number', sort: 'document_number'),
                    $this->col('issued_date', 'Issued', format: 'date', sort: 'issued_date'),
                    $this->col('expiry_date', 'Expiry', format: 'date', sort: 'expiry_date'),
                    $this->col('status', 'Status', format: 'enum', sort: 'status'),
                ],
                search: ['vehicle.registration_number', 'vehicle.vehicle_number', 'document_number'],
                relations: ['vehicle'],
                filters: [
                    $this->filter('document_type', 'Document Type', 'document_type'),
                    $this->filter('status', 'Status', 'status'),
                ],
                dateColumn: 'expiry_date',
                defaultSort: 'expiry_date',
                scope: static fn ($query) => $query->whereNotNull('expiry_date'),
            ),
            new ReportDefinition(
                key: 'vehicle-rental.tax-withholding-traceability',
                title: 'Rental Tax and Withholding Traceability',
                group: 'Vehicle Rental',
                model: TaxTransaction::class,
                columns: [
                    $this->col('transaction_date', 'Date', format: 'date', sort: 'transaction_date'),
                    $this->col('source_number', 'Invoice / Payable', sort: 'source_number'),
                    $this->col('party_type', 'Party Type', format: 'enum', sort: 'party_type'),
                    $this->col('tax_code', 'Tax Code', sort: 'tax_code'),
                    $this->col('tax_name', 'Tax', sort: 'tax_name'),
                    $this->money('taxable_amount', 'Taxable Amount'),
                    $this->money('tax_amount', 'Tax Amount'),
                    $this->money('withholding_amount', 'Withholding'),
                ],
                search: ['source_number', 'tax_code', 'tax_name'],
                filters: [
                    $this->filter('tax_type', 'Tax Type', 'tax_type'),
                    $this->filter('party_type', 'Party Type', 'party_type'),
                ],
                dateColumn: 'transaction_date',
                defaultSort: 'transaction_date',
                scope: static fn ($query) => $query
                    ->where('source_type', 'invoice')
                    ->whereExists(static fn ($links) => $links
                        ->selectRaw('1')
                        ->from('rental_invoice_links')
                        ->whereColumn('rental_invoice_links.invoice_id', 'tax_transactions.source_id')),
            ),
        ];
    }

    private function rentalAgreementReport(string $key, string $title): ReportDefinition
    {
        $partyName = static fn (RentalAgreement $agreement): ?string => $agreement->party_type->value === 'customer'
            ? ($agreement->customer?->display_name ?? $agreement->customer?->name)
            : ($agreement->supplier?->display_name ?? $agreement->supplier?->name);
        $vehicleLabel = static fn (RentalAgreement $agreement): ?string => $agreement->vehicles
            ->first()?->vehicle?->registration_number
            ?? $agreement->vehicles->first()?->vehicle?->vehicle_number;

        return new ReportDefinition(
            key: $key,
            title: $title,
            group: 'Vehicle Rental',
            model: RentalAgreement::class,
            columns: $this->rentalAgreementColumns($partyName, $vehicleLabel),
            search: ['agreement_number', 'customer.name', 'supplier.name', 'vehicles.vehicle.registration_number'],
            relations: ['customer', 'supplier', 'vehicles.vehicle'],
            filters: [
                $this->filter('status', 'Status', 'status'),
                $this->filter('direction', 'Direction', 'direction'),
                $this->filter('rental_type', 'Rental Type', 'rental_type'),
            ],
            dateColumn: 'agreement_date',
            defaultSort: 'agreement_date',
            orientation: 'landscape',
        );
    }

    /**
     * @return list<ReportColumn>
     */
    private function rentalAgreementColumns(\Closure $partyName, \Closure $vehicleLabel): array
    {
        return [
            $this->col('agreement_date', 'Date', format: 'date', sort: 'agreement_date'),
            $this->col('agreement_number', 'Agreement', sort: 'agreement_number'),
            $this->col('direction', 'Direction', format: 'enum', sort: 'direction'),
            new ReportColumn('party', 'Party', value: $partyName),
            new ReportColumn('vehicle', 'Vehicle', value: $vehicleLabel),
            $this->col('start_at', 'Start', format: 'datetime', sort: 'start_at'),
            $this->col('expected_end_at', 'Expected End', format: 'datetime', sort: 'expected_end_at'),
            $this->col('status', 'Status', format: 'enum', sort: 'status'),
        ];
    }

    private function rentalInvoiceReport(string $key, string $title, string $direction): ReportDefinition
    {
        return new ReportDefinition(
            key: $key,
            title: $title,
            group: 'Vehicle Rental',
            model: Invoice::class,
            columns: [
                $this->col('invoice_date', 'Date', format: 'date', sort: 'invoice_date'),
                $this->col('invoice_number', 'Invoice', sort: 'invoice_number'),
                $this->col('party_type', 'Party Type', format: 'enum', sort: 'party_type'),
                $this->money('subtotal', 'Subtotal'),
                $this->money('tax_total', 'Tax'),
                $this->money('grand_total', 'Total'),
                $this->money('balance_due', 'Outstanding'),
                $this->col('status', 'Status', format: 'enum', sort: 'status'),
            ],
            search: ['invoice_number', 'party_type'],
            filters: [$this->filter('status', 'Status', 'status')],
            dateColumn: 'invoice_date',
            defaultSort: 'invoice_date',
            constraints: ['invoice_type' => 'rental', 'direction' => $direction],
        );
    }

    private function rentalOutstandingReport(string $key, string $title, string $direction): ReportDefinition
    {
        return new ReportDefinition(
            key: $key,
            title: $title,
            group: 'Vehicle Rental',
            model: InvoiceBalance::class,
            columns: [
                $this->col('invoice', 'Invoice', 'invoice.invoice_number'),
                $this->col('invoice_date', 'Invoice Date', 'invoice.invoice_date', 'date'),
                $this->col('due_date', 'Due Date', 'invoice.due_date', 'date'),
                $this->money('invoice_total', 'Total'),
                $this->money('paid_amount', 'Paid'),
                $this->money('remaining_amount', 'Outstanding'),
                $this->col('status', 'Status', format: 'enum', sort: 'status'),
            ],
            search: ['invoice.invoice_number'],
            relations: ['invoice'],
            filters: [$this->filter('status', 'Status', 'status')],
            defaultSort: 'id',
            scope: static fn ($query) => $query
                ->where('remaining_amount', '>', '0')
                ->whereHas('invoice', fn ($invoice) => $invoice
                    ->where('invoice_type', 'rental')
                    ->where('direction', $direction)
                    ->whereNotIn('status', ['cancelled', 'void'])),
        );
    }

    private function approvedRentalChargeTotal(RentalAgreement $agreement): string
    {
        return $this->math->sum(
            $agreement->charges
                ->filter(fn (RentalCharge $charge): bool => $charge->status->value === 'approved')
                ->pluck('total_amount')
                ->map(fn ($value): string => (string) $value)
                ->all(),
        );
    }

    private function companyBorneRentalExpenseTotal(RentalAgreement $agreement): string
    {
        return $this->math->sum(
            $agreement->expenses
                ->filter(fn ($expense): bool => $expense->financial_treatment->value === 'company_borne')
                ->pluck('amount')
                ->map(fn ($value): string => (string) $value)
                ->all(),
        );
    }

    /**
     * @param  array<string, mixed>  $constraints
     */
    private function taxTransactions(string $key, string $title, array $constraints = []): ReportDefinition
    {
        return new ReportDefinition(
            key: $key,
            title: $title,
            group: 'Tax',
            model: TaxTransaction::class,
            columns: [
                $this->col('transaction_date', 'Date', format: 'date', sort: 'transaction_date'),
                $this->col('tax_code', 'Tax Code', sort: 'tax_code'),
                $this->col('tax_name', 'Tax Name', sort: 'tax_name'),
                $this->col('tax_type', 'Tax Type', sort: 'tax_type'),
                $this->col('source_number', 'Source Number', sort: 'source_number'),
                $this->col('party_type', 'Party Type', format: 'enum', sort: 'party_type'),
                $this->money('taxable_amount', 'Taxable'),
                $this->money('tax_amount', 'Tax'),
                $this->money('withholding_amount', 'Withholding'),
                $this->col('is_withholding', 'WHT', format: 'boolean', sort: 'is_withholding'),
            ],
            search: ['tax_code', 'tax_name', 'tax_type', 'source_number'],
            filters: [
                $this->filter('tax_type', 'Tax Type', 'tax_type'),
                $this->filter('tax_code', 'Tax Code', 'tax_code'),
                $this->filter('party_type', 'Party Type', 'party_type'),
            ],
            dateColumn: 'transaction_date',
            defaultSort: 'transaction_date',
            constraints: $constraints,
            description: 'Tax Engine transaction report export.',
            orientation: 'landscape',
        );
    }

    private function party(string $key, string $title, string $model): ReportDefinition
    {
        return $this->masters($key, $title, $model, [
            $this->col('code', 'Code', sort: 'code'), $this->col('name', 'Name', sort: 'name'), $this->col('display_name', 'Display Name', sort: 'display_name'),
            $this->col('email', 'Email', sort: 'email'), $this->col('phone', 'Phone', sort: 'phone'), $this->col('status', 'Status', format: 'enum', sort: 'status'), $this->money('credit_limit', 'Credit Limit', false),
        ], ['code', 'name', 'display_name', 'email', 'phone']);
    }

    private function inventory(string $key, string $title, string $model, array $columns, array $search, array $relations = [], ?string $dateColumn = null): ReportDefinition
    {
        return $this->definition($key, $title, 'Inventory', $model, $columns, $search, $relations, $dateColumn);
    }

    private function inventoryFlow(string $key, string $title, string $model, string $dateColumn, string $quantityColumn): ReportDefinition
    {
        return $this->inventory($key, $title, $model, [
            $this->col($dateColumn, 'Date', format: 'date', sort: $dateColumn), $this->itemCol(), $this->col('warehouse', 'Warehouse', 'warehouse.name'),
            $this->col('batch', 'Batch', 'batch.batch_number'), $this->qty($quantityColumn, 'Quantity'), $this->col('uom', 'UOM', 'baseUom.code'), $this->col('status', 'Status', format: 'enum', sort: 'status'),
        ], ['item.code', 'item.name', 'warehouse.name', 'batch.batch_number'], ['item', 'baseUom', 'warehouse', 'batch'], $dateColumn);
    }

    private function purchase(string $key, string $title, string $model, string $dateColumn, string $numberColumn, string $amountColumn): ReportDefinition
    {
        return $this->definition($key, $title, 'Purchase', $model, [
            $this->col($dateColumn, 'Date', format: 'date', sort: $dateColumn), $this->col($numberColumn, 'Number', sort: $numberColumn),
            $this->col('supplier', 'Supplier', 'supplier.name'), $this->col('warehouse', 'Warehouse', 'warehouse.name'), $this->money($amountColumn, 'Amount'), $this->col('status', 'Status', format: 'enum', sort: 'status'),
        ], [$numberColumn, 'supplier.name', 'warehouse.name'], ['supplier', 'warehouse'], $dateColumn);
    }

    private function serviceJob(string $key, string $title): ReportDefinition
    {
        return $this->definition($key, $title, 'Vehicle Service', VehicleServiceJob::class, [
            $this->col('job_date', 'Date', format: 'date', sort: 'job_date'), $this->col('job_number', 'Job', sort: 'job_number'), $this->col('customer', 'Customer', 'customer.name'),
            $this->col('vehicle', 'Vehicle', 'vehicle.vehicle_number'), $this->col('supervisor', 'Supervisor', 'supervisor.display_name'), $this->money('grand_total', 'Total'), $this->col('status', 'Status', format: 'enum', sort: 'status'),
        ], ['job_number', 'customer.name', 'vehicle.vehicle_number', 'supervisor.display_name'], ['customer', 'vehicle', 'supervisor'], 'job_date');
    }

    private function profitabilityReport(): ReportDefinition
    {
        return new ReportDefinition(
            key: 'vehicle-service.profitability',
            title: 'Vehicle Service Profitability Report',
            group: 'Vehicle Service',
            model: VehicleServiceJob::class,
            columns: [
                $this->col('job_date', 'Date', format: 'date', sort: 'job_date'),
                $this->col('job_number', 'Job', sort: 'job_number'),
                $this->col('customer', 'Customer', 'customer.name'),
                $this->col('vehicle', 'Vehicle', 'vehicle.vehicle_number'),
                $this->calculatedMoney('revenue', 'Revenue'),
                $this->calculatedMoney('direct_cost', 'Direct Cost'),
                $this->calculatedMoney('commission', 'Commission'),
                $this->calculatedMoney('gross_profit', 'Gross Profit'),
                new ReportColumn(
                    key: 'margin',
                    label: 'Margin %',
                    format: 'decimal',
                    value: fn (VehicleServiceJob $job): string => $this->profitability->value($job, 'margin'),
                ),
                $this->col('status', 'Status', format: 'enum', sort: 'status'),
            ],
            search: ['job_number', 'customer.name', 'vehicle.vehicle_number'],
            relations: ['customer', 'vehicle', 'lines', 'employeeAssignments'],
            filters: [$this->filter('status', 'Status', 'status')],
            dateColumn: 'job_date',
            defaultSort: 'job_date',
            defaultDirection: 'desc',
            description: 'Revenue, direct line cost, stored commission, gross profit, and margin by service job.',
            orientation: 'landscape',
        );
    }

    private function labour(string $key, string $title): ReportDefinition
    {
        return $this->definition($key, $title, 'Vehicle Service', VehicleServiceLineEmployee::class, [
            $this->col('assigned_at', 'Assigned', format: 'datetime', sort: 'assigned_at'), $this->col('job', 'Job', 'job.job_number'), $this->col('employee', 'Employee', 'employee.display_name'),
            $this->qty('assigned_hours', 'Hours'), $this->money('rate', 'Rate', false), $this->money('commission_amount', 'Commission'), $this->col('completed_at', 'Completed', format: 'datetime', sort: 'completed_at'),
        ], ['job.job_number', 'employee.display_name'], ['job', 'employee'], 'assigned_at');
    }

    private function invoice(string $key, string $title, string $model, string $dateColumn, string $numberColumn, string $amountColumn): ReportDefinition
    {
        return $this->definition($key, $title, 'Invoice & Payment', $model, [
            $this->col($dateColumn, 'Date', format: 'date', sort: $dateColumn), $this->col($numberColumn, 'Number', sort: $numberColumn), $this->col('party_name', 'Party', sort: 'party_name'),
            $this->money($amountColumn, 'Amount'), $this->money('paid_total', 'Paid'), $this->money('balance_due', 'Balance'), $this->col('status', 'Status', format: 'enum', sort: 'status'),
        ], [$numberColumn, 'party_name'], [], $dateColumn);
    }

    private function payment(string $key, string $title, string $model, string $dateColumn, string $numberColumn, string $amountColumn): ReportDefinition
    {
        return new ReportDefinition(
            key: $key,
            title: $title,
            group: 'Invoice & Payment',
            model: $model,
            columns: [
                $this->col($dateColumn, 'Date', format: 'date', sort: $dateColumn),
                $this->col($numberColumn, 'Number', sort: $numberColumn),
                $this->col('party_type', 'Party Type', format: 'enum', sort: 'party_type'),
                $this->col('party_id', 'Party ID', sort: 'party_id'),
                $this->money($amountColumn, 'Amount'),
                $this->money('allocated_amount', 'Allocated'),
                $this->money('unapplied_amount', 'Unapplied'),
                $this->col('status', 'Status', format: 'enum', sort: 'status'),
            ],
            search: [$numberColumn, 'reference_number', 'party_type'],
            filters: [
                $this->filter('status', 'Status', 'status'),
                $this->filter('direction', 'Direction', 'direction'),
                $this->filter('party_type', 'Party Type', 'party_type'),
                $this->filter('party_id', 'Party ID', 'party_id'),
            ],
            dateColumn: $dateColumn,
            defaultSort: $dateColumn,
            defaultDirection: 'desc',
            description: 'Payment module register with allocation and unapplied balance totals.',
        );
    }

    private function creditReport(string $key, string $title, string $partyType): ReportDefinition
    {
        return new ReportDefinition(
            key: $key,
            title: $title,
            group: 'Invoice & Payment',
            model: PaymentUnappliedBalance::class,
            columns: [
                $this->col('payment_date', 'Payment Date', 'payment.payment_date', 'date'),
                $this->col('payment', 'Payment', 'payment.payment_number'),
                $this->col('party_id', 'Party ID', sort: 'party_id'),
                $this->col('balance_type', 'Type', format: 'enum', sort: 'balance_type'),
                $this->money('original_amount', 'Original'),
                $this->money('allocated_amount', 'Allocated'),
                $this->money('refunded_amount', 'Refunded'),
                $this->money('remaining_amount', 'Credit'),
                $this->col('status', 'Status', format: 'enum', sort: 'status'),
            ],
            search: ['payment.payment_number', 'balance_type'],
            relations: ['payment'],
            filters: [$this->filter('status', 'Status', 'status')],
            dateColumn: 'payment.payment_date',
            defaultSort: 'id',
            defaultDirection: 'desc',
            scope: static fn ($query) => $query
                ->where('party_type', $partyType)
                ->where('remaining_amount', '>', '0')
                ->whereIn('balance_type', ['credit', 'overpayment', 'advance', 'deposit']),
            description: 'Open credit balances owned by Payment and available for later settlement.',
        );
    }

    /**
     * @param  list<string>  $methodTypes
     */
    private function paymentLineReport(string $key, string $title, array $methodTypes, ?string $direction = null): ReportDefinition
    {
        return new ReportDefinition(
            key: $key,
            title: $title,
            group: 'Invoice & Payment',
            model: PaymentLine::class,
            columns: [
                $this->col('payment_date', 'Payment Date', 'payment.payment_date', 'date'),
                $this->col('payment', 'Payment', 'payment.payment_number'),
                $this->col('method', 'Method', 'paymentMethod.name'),
                $this->col('method_type', 'Type', 'paymentMethod.method_type', 'enum'),
                $this->col('reference_number', 'Reference', sort: 'reference_number'),
                $this->money('amount', 'Amount'),
                $this->money('cleared_amount', 'Cleared'),
                $this->col('status', 'Status', format: 'enum', sort: 'status'),
            ],
            search: ['payment.payment_number', 'reference_number', 'paymentMethod.name'],
            relations: ['payment', 'paymentMethod'],
            filters: [
                $this->filter('status', 'Status', 'status'),
                $this->filter('method_type', 'Payment Method', 'paymentMethod.method_type'),
            ],
            dateColumn: 'payment.payment_date',
            defaultSort: 'id',
            defaultDirection: 'desc',
            scope: static function ($query) use ($methodTypes, $direction) {
                $query->whereHas('paymentMethod', fn ($method) => $method->whereIn('method_type', $methodTypes));

                if ($direction !== null) {
                    $query->whereHas('payment', fn ($payment) => $payment->where('direction', $direction));
                }

                return $query;
            },
            description: 'Payment line settlement report driven by Payment method configuration.',
            orientation: 'landscape',
        );
    }

    private function aging(string $key, string $title, string $direction): ReportDefinition
    {
        return new ReportDefinition(
            key: $key,
            title: $title,
            group: 'Finance',
            model: InvoiceBalance::class,
            columns: [
                $this->col('invoice_number', 'Invoice', 'invoice.invoice_number'),
                $this->col('invoice_date', 'Invoice Date', 'invoice.invoice_date', 'date'),
                $this->col('due_date', 'Due Date', 'invoice.due_date', 'date'),
                new ReportColumn(
                    key: 'days_overdue',
                    label: 'Days Overdue',
                    value: static function (InvoiceBalance $balance): int {
                        $dueDate = $balance->invoice?->due_date ?? $balance->invoice?->invoice_date;
                        if ($dueDate === null || $dueDate->isFuture()) {
                            return 0;
                        }

                        return (int) $dueDate->startOfDay()->diffInDays(now()->startOfDay());
                    },
                ),
                new ReportColumn(
                    key: 'aging_bucket',
                    label: 'Aging Bucket',
                    value: static function (InvoiceBalance $balance): string {
                        $dueDate = $balance->invoice?->due_date ?? $balance->invoice?->invoice_date;
                        if ($dueDate === null || $dueDate->isFuture()) {
                            return 'Current';
                        }

                        $days = (int) $dueDate->startOfDay()->diffInDays(now()->startOfDay());

                        return match (true) {
                            $days <= 30 => '1-30',
                            $days <= 60 => '31-60',
                            $days <= 90 => '61-90',
                            default => '90+',
                        };
                    },
                ),
                $this->money('invoice_total', 'Invoice Total'),
                $this->money('remaining_amount', 'Outstanding'),
                $this->col('status', 'Status', format: 'enum', sort: 'status'),
            ],
            search: ['invoice.invoice_number'],
            relations: ['invoice'],
            defaultSort: 'id',
            defaultDirection: 'asc',
            scope: static fn ($query) => $query
                ->where('remaining_amount', '>', '0')
                ->whereHas('invoice', fn ($invoice) => $invoice
                    ->where('direction', $direction)
                    ->whereNotIn('status', ['cancelled', 'void'])),
            description: 'Outstanding invoice balances grouped by age from the Invoice source of truth.',
        );
    }

    private function chartOfAccounts(): ReportDefinition
    {
        return new ReportDefinition(
            key: 'finance.chart-of-accounts',
            title: 'Chart of Accounts',
            group: 'Finance',
            model: FinanceAccount::class,
            columns: [
                $this->col('code', 'Account', sort: 'code'),
                $this->col('name', 'Account Name', sort: 'name'),
                $this->col('account_type', 'Type', 'accountType.name'),
                $this->col('category', 'Category', 'accountCategory.name'),
                $this->col('parent', 'Parent', 'parent.code'),
                $this->col('normal_balance', 'Normal Balance', format: 'enum', sort: 'normal_balance'),
                $this->col('is_posting_account', 'Postable', format: 'boolean', sort: 'is_posting_account'),
                $this->col('is_active', 'Active', format: 'boolean', sort: 'is_active'),
            ],
            search: ['code', 'name', 'accountType.name', 'accountCategory.name'],
            relations: ['accountType', 'accountCategory', 'parent'],
            defaultSort: 'code',
            defaultDirection: 'asc',
        );
    }

    private function cashFlowReport(): ReportDefinition
    {
        return new ReportDefinition(
            key: 'finance.cash-flow',
            title: 'Cash Flow',
            group: 'Finance',
            model: FinanceLedgerEntry::class,
            columns: [
                $this->col('entry_date', 'Date', format: 'date', sort: 'entry_date'),
                $this->col('account', 'Cash/Bank Account', 'account.code'),
                $this->col('account_name', 'Account Name', 'account.name'),
                $this->col('source_module', 'Source Module', sort: 'source_module'),
                $this->col('source_number', 'Source Number', sort: 'source_number'),
                $this->money('debit', 'Inflow'),
                $this->money('credit', 'Outflow'),
            ],
            search: ['account.code', 'account.name', 'source_number'],
            relations: ['account'],
            dateColumn: 'entry_date',
            defaultSort: 'entry_date',
            scope: static fn ($query) => $query->whereHas('account', fn ($account) => $account
                ->where('is_cash_account', true)
                ->orWhere('is_bank_account', true)),
            description: 'Ledger-derived cash and bank account movements.',
        );
    }

    private function col(string $key, string $label, ?string $path = null, string $format = 'text', ?string $sort = null): ReportColumn
    {
        return new ReportColumn($key, $label, $path, $sort, $format);
    }

    private function qty(string $key, string $label, bool $summarize = true): ReportColumn
    {
        return new ReportColumn($key, $label, null, $key, 'decimal', $summarize);
    }

    private function money(string $key, string $label, bool $summarize = true): ReportColumn
    {
        return new ReportColumn($key, $label, null, $key, 'money', $summarize);
    }

    private function calculatedMoney(string $key, string $label): ReportColumn
    {
        return new ReportColumn(
            key: $key,
            label: $label,
            format: 'money',
            summarize: true,
            value: fn (VehicleServiceJob $job): string => $this->profitability->value($job, $key),
        );
    }

    private function itemCol(): ReportColumn
    {
        return $this->col('item', 'Item', 'item.name');
    }

    private function filter(string $key, string $label, string $field): ReportFilter
    {
        return new ReportFilter($key, $label, $field);
    }
}
