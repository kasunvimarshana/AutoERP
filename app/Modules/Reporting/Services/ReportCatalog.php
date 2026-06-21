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
use Modules\VehicleRental\Models\RentalBillingPeriod;
use Modules\VehicleRental\Models\RentalCalculationLine;
use Modules\VehicleRental\Models\RentalCalculationRun;
use Modules\VehicleRental\Models\RentalCustodyEvent;
use Modules\VehicleRental\Models\RentalDepositRequirement;
use Modules\VehicleRental\Models\RentalExpenseAllocation;
use Modules\VehicleRental\Models\RentalVehicleAllocation;
use Modules\VehicleRental\Models\RentalVehicleReplacement;
use Modules\VehicleRental\Models\VehicleFinanceInstallment;
use Modules\VehicleRental\Models\RentalExpense;
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
            $this->voucherPayment('voucher.receipts', 'Receipt Voucher Register', 'inbound'),
            $this->voucherPayment('voucher.payments', 'Payment Voucher Register', 'outbound'),
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
            $this->voucherPaymentReversal('voucher.reversals.payment', 'Payment Reversal Voucher Register'),
            $this->paymentLineReport('payment.cheque-status', 'Cheque Status Report', ['cheque']),
            $this->paymentLineReport('payment.cheque-received', 'Cheque Received Register', ['cheque'], 'inbound'),
            $this->paymentLineReport('payment.cheque-issued', 'Cheque Issued Register', ['cheque'], 'outbound'),
            $this->paymentLineReport('payment.bank-transfer', 'Bank Transfer Report', ['bank_transfer', 'direct_debit']),
            $this->paymentLineReport('payment.bank-receipts', 'Bank Receipt Register', ['bank_transfer', 'direct_debit'], 'inbound'),
            $this->paymentLineReport('payment.bank-payments', 'Bank Payment Register', ['bank_transfer', 'direct_debit'], 'outbound'),
            $this->paymentLineReport('payment.cash-collection', 'Cash Collection Report', ['cash'], 'inbound'),
            $this->paymentLineReport('payment.cash-payments', 'Cash Payment Register', ['cash'], 'outbound'),

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
            $this->voucherJournal('voucher.journals', 'Journal Voucher Register', 'general'),
            $this->voucherJournal('voucher.contra', 'Contra Voucher Register', 'contra'),
            $this->voucherJournal('voucher.adjustments', 'Adjustment Voucher Register', 'adjustment'),
            $this->voucherJournal('voucher.opening', 'Opening Voucher Register', 'opening'),
            $this->voucherJournal('voucher.reversals.finance', 'Finance Reversal Voucher Register', 'reversal'),
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
        return [
            new ReportDefinition(
                key: 'vehicle-rental.fleet-availability',
                title: 'Fleet Availability',
                group: 'Vehicle Rental',
                model: Vehicle::class,
                columns: [
                    $this->col('vehicle_number', 'Vehicle', sort: 'vehicle_number'),
                    $this->col('registration_number', 'Registration', sort: 'registration_number'),
                    $this->col('make', 'Make', 'make.name'),
                    $this->col('model', 'Model', 'model.name'),
                    $this->col('category', 'Category', 'category.name'),
                    $this->col('status', 'Status', format: 'enum', sort: 'status'),
                ],
                search: ['vehicle_number', 'registration_number', 'make.name', 'model.name'],
                relations: ['make', 'model', 'category'],
                filters: [$this->filter('status', 'Status', 'status')],
                defaultSort: 'vehicle_number',
            ),
            $this->rentalAgreementReport('vehicle-rental.agreement-register', 'Rental Agreement Register'),
            new ReportDefinition(
                key: 'vehicle-rental.active-rentals',
                title: 'Active Rental Agreements',
                group: 'Vehicle Rental',
                model: RentalAgreement::class,
                columns: $this->rentalAgreementColumns(),
                search: ['agreement_number', 'customer.name', 'supplier.name'],
                relations: ['customer', 'supplier', 'allocations.vehicle'],
                filters: [$this->filter('agreement_kind', 'Agreement Kind', 'agreement_kind')],
                dateColumn: 'agreement_date',
                defaultSort: 'agreement_date',
                constraints: ['status' => 'active'],
            ),
            new ReportDefinition(
                key: 'vehicle-rental.overdue-rentals',
                title: 'Overdue Rental Agreements',
                group: 'Vehicle Rental',
                model: RentalAgreement::class,
                columns: $this->rentalAgreementColumns(),
                search: ['agreement_number', 'customer.name', 'supplier.name'],
                relations: ['customer', 'supplier', 'allocations.vehicle'],
                dateColumn: 'ends_at',
                defaultSort: 'ends_at',
                scope: static fn ($query) => $query->where('status', 'active')->where('ends_at', '<', now()),
            ),
            new ReportDefinition(
                key: 'vehicle-rental.allocation-history',
                title: 'Vehicle Allocation History',
                group: 'Vehicle Rental',
                model: RentalVehicleAllocation::class,
                columns: [
                    $this->col('allocation_number', 'Allocation', sort: 'allocation_number'),
                    $this->col('agreement', 'Agreement', 'agreement.agreement_number'),
                    $this->col('agreement_kind', 'Agreement Kind', 'agreement.agreement_kind', 'enum'),
                    $this->col('vehicle', 'Vehicle', 'vehicle.registration_number'),
                    $this->col('vehicle_source_type', 'Source', format: 'enum', sort: 'vehicle_source_type'),
                    $this->col('allocated_from', 'From', format: 'datetime', sort: 'allocated_from'),
                    $this->col('allocated_to', 'To', format: 'datetime', sort: 'allocated_to'),
                    $this->col('status', 'Status', format: 'enum', sort: 'status'),
                ],
                search: ['allocation_number', 'agreement.agreement_number', 'vehicle.registration_number'],
                relations: ['agreement', 'vehicle'],
                filters: [
                    $this->filter('status', 'Status', 'status'),
                    $this->filter('vehicle_source_type', 'Vehicle Source', 'vehicle_source_type'),
                ],
                dateColumn: 'allocated_from',
                defaultSort: 'allocated_from',
            ),
            new ReportDefinition(
                key: 'vehicle-rental.custody-history',
                title: 'Vehicle Custody and Handover History',
                group: 'Vehicle Rental',
                model: RentalCustodyEvent::class,
                columns: [
                    $this->col('event_number', 'Event', sort: 'event_number'),
                    $this->col('occurred_at', 'Date/Time', format: 'datetime', sort: 'occurred_at'),
                    $this->col('vehicle', 'Vehicle', 'vehicle.registration_number'),
                    $this->col('allocation', 'Allocation', 'allocation.allocation_number'),
                    $this->col('event_type', 'Event Type', format: 'enum', sort: 'event_type'),
                    $this->col('from_role', 'From', sort: 'from_role'),
                    $this->col('to_role', 'To', sort: 'to_role'),
                    $this->qty('odometer', 'Odometer', false),
                    $this->col('status', 'Status', format: 'enum', sort: 'status'),
                ],
                search: ['event_number', 'vehicle.registration_number', 'allocation.allocation_number'],
                relations: ['vehicle', 'allocation'],
                filters: [
                    $this->filter('event_type', 'Event Type', 'event_type'),
                    $this->filter('status', 'Status', 'status'),
                ],
                dateColumn: 'occurred_at',
                defaultSort: 'occurred_at',
            ),
            new ReportDefinition(
                key: 'vehicle-rental.replacement-history',
                title: 'Vehicle Replacement History',
                group: 'Vehicle Rental',
                model: RentalVehicleReplacement::class,
                columns: [
                    $this->col('replacement_number', 'Replacement', sort: 'replacement_number'),
                    $this->col('replacement_at', 'Date/Time', format: 'datetime', sort: 'replacement_at'),
                    $this->col('agreement', 'Agreement', 'agreement.agreement_number'),
                    $this->col('old_vehicle', 'Old Vehicle', 'oldAllocation.vehicle.registration_number'),
                    $this->col('new_vehicle', 'New Vehicle', 'newAllocation.vehicle.registration_number'),
                    $this->col('reason_code', 'Reason Code', sort: 'reason_code'),
                    $this->col('status', 'Status', format: 'enum', sort: 'status'),
                ],
                search: ['replacement_number', 'agreement.agreement_number', 'oldAllocation.vehicle.registration_number', 'newAllocation.vehicle.registration_number'],
                relations: ['agreement', 'oldAllocation.vehicle', 'newAllocation.vehicle'],
                filters: [$this->filter('status', 'Status', 'status')],
                dateColumn: 'replacement_at',
                defaultSort: 'replacement_at',
            ),
            new ReportDefinition(
                key: 'vehicle-rental.running-chart',
                title: 'Daily Running Chart',
                group: 'Vehicle Rental',
                model: RentalUsageLog::class,
                columns: [
                    $this->col('usage_date', 'Date', format: 'date', sort: 'usage_date'),
                    $this->col('usage_number', 'Running Chart', sort: 'usage_number'),
                    $this->col('vehicle', 'Vehicle', 'vehicle.registration_number'),
                    $this->col('allocation', 'Allocation', 'allocation.allocation_number'),
                    $this->col('driver', 'Driver', 'driver.display_name'),
                    $this->qty('start_odometer', 'Start KM', false),
                    $this->qty('end_odometer', 'Finish KM', false),
                    $this->qty('distance_km', 'Distance'),
                    $this->qty('chargeable_distance_km', 'Chargeable KM'),
                    $this->qty('garage_distance_km', 'Garage KM'),
                    $this->col('status', 'Status', format: 'enum', sort: 'status'),
                ],
                search: ['usage_number', 'vehicle.registration_number', 'allocation.allocation_number', 'driver.display_name'],
                relations: ['vehicle', 'allocation', 'driver'],
                filters: [$this->filter('status', 'Status', 'status')],
                dateColumn: 'usage_date',
                defaultSort: 'usage_date',
                orientation: 'landscape',
            ),
            new ReportDefinition(
                key: 'vehicle-rental.driver-overtime',
                title: 'Driver Overtime and Night-Out',
                group: 'Vehicle Rental',
                model: RentalUsageLog::class,
                columns: [
                    $this->col('usage_date', 'Date', format: 'date', sort: 'usage_date'),
                    $this->col('driver', 'Driver', 'driver.display_name'),
                    $this->col('vehicle', 'Vehicle', 'vehicle.registration_number'),
                    $this->col('allocation', 'Allocation', 'allocation.allocation_number'),
                    new ReportColumn('normal_ot_hours', 'Normal OT Hours', format: 'decimal', summarize: true, value: fn (RentalUsageLog $log): string => $this->math->div((string) $log->normal_overtime_minutes, '60')),
                    new ReportColumn('double_ot_hours', 'Double OT Hours', format: 'decimal', summarize: true, value: fn (RentalUsageLog $log): string => $this->math->div((string) $log->double_overtime_minutes, '60')),
                    new ReportColumn('triple_ot_hours', 'Triple OT Hours', format: 'decimal', summarize: true, value: fn (RentalUsageLog $log): string => $this->math->div((string) $log->triple_overtime_minutes, '60')),
                    $this->qty('night_out_count', 'Night-Outs'),
                ],
                search: ['driver.display_name', 'vehicle.registration_number', 'allocation.allocation_number'],
                relations: ['driver', 'vehicle', 'allocation'],
                dateColumn: 'usage_date',
                defaultSort: 'usage_date',
                scope: static fn ($query) => $query->whereIn('status', ['approved', 'consumed'])
                    ->where(static fn ($hours) => $hours
                        ->where('normal_overtime_minutes', '>', 0)
                        ->orWhere('double_overtime_minutes', '>', 0)
                        ->orWhere('triple_overtime_minutes', '>', 0)
                        ->orWhere('night_out_count', '>', 0)),
            ),
            $this->rentalCalculationReport('vehicle-rental.customer-revenue', 'Customer Rental Revenue Calculations', 'revenue'),
            $this->rentalCalculationReport('vehicle-rental.owner-cost', 'Vehicle Owner Cost Calculations', 'cost'),
            $this->rentalInvoiceReport('vehicle-rental.customer-invoices', 'Customer Rental Invoices', 'outbound'),
            $this->rentalInvoiceReport('vehicle-rental.owner-payables', 'Vehicle Owner Payables', 'inbound'),
            $this->rentalOutstandingReport('vehicle-rental.customer-outstanding', 'Customer Rental Outstanding', 'outbound'),
            $this->rentalOutstandingReport('vehicle-rental.owner-outstanding', 'Vehicle Owner Payable Outstanding', 'inbound'),
            new ReportDefinition(
                key: 'vehicle-rental.deposit-liability',
                title: 'Rental Deposit Liability',
                group: 'Vehicle Rental',
                model: RentalDepositRequirement::class,
                columns: [
                    $this->col('agreement', 'Agreement', 'agreement.agreement_number'),
                    $this->col('customer', 'Customer', 'agreement.customer.display_name'),
                    $this->money('required_amount', 'Required'),
                    $this->money('received_amount', 'Received'),
                    $this->money('applied_amount', 'Applied'),
                    $this->money('refunded_amount', 'Refunded'),
                    $this->money('balance_amount', 'Liability Balance'),
                    $this->col('status', 'Status', format: 'enum', sort: 'status'),
                ],
                search: ['agreement.agreement_number', 'agreement.customer.name'],
                relations: ['agreement.customer'],
                filters: [$this->filter('status', 'Status', 'status')],
                defaultSort: 'id',
            ),
            new ReportDefinition(
                key: 'vehicle-rental.expenses',
                title: 'Rental Expense and Recovery',
                group: 'Vehicle Rental',
                model: RentalExpense::class,
                columns: [
                    $this->col('expense_date', 'Date', format: 'date', sort: 'expense_date'),
                    $this->col('expense_number', 'Expense', sort: 'expense_number'),
                    $this->col('vehicle', 'Vehicle', 'vehicle.registration_number'),
                    $this->col('expense_type', 'Type', format: 'enum', sort: 'expense_type'),
                    $this->money('net_amount', 'Net'),
                    $this->money('tax_amount', 'Tax'),
                    $this->money('gross_amount', 'Gross'),
                    $this->col('status', 'Status', format: 'enum', sort: 'status'),
                ],
                search: ['expense_number', 'vehicle.registration_number', 'reference_number'],
                relations: ['vehicle'],
                filters: [
                    $this->filter('expense_type', 'Expense Type', 'expense_type'),
                    $this->filter('status', 'Status', 'status'),
                ],
                dateColumn: 'expense_date',
                defaultSort: 'expense_date',
            ),
            new ReportDefinition(
                key: 'vehicle-rental.owner-deductions',
                title: 'Vehicle Owner Deductions',
                group: 'Vehicle Rental',
                model: RentalExpenseAllocation::class,
                columns: [
                    $this->col('expense_date', 'Date', 'expense.expense_date', 'date'),
                    $this->col('expense', 'Expense', 'expense.expense_number'),
                    $this->col('vehicle', 'Vehicle', 'expense.vehicle.registration_number'),
                    $this->col('owner', 'Owner', 'supplier.display_name'),
                    $this->money('net_amount', 'Deduction'),
                    $this->money('withholding_amount', 'Withholding'),
                    $this->money('total_amount', 'Total'),
                    $this->col('status', 'Status', format: 'enum', sort: 'status'),
                ],
                search: ['expense.expense_number', 'expense.vehicle.registration_number', 'supplier.name'],
                relations: ['expense.vehicle', 'supplier'],
                dateColumn: 'expense.expense_date',
                defaultSort: 'id',
                constraints: ['allocation_type' => 'owner_deduction'],
            ),
            new ReportDefinition(
                key: 'vehicle-rental.lease-installments',
                title: 'Vehicle Finance Installments',
                group: 'Vehicle Rental',
                model: VehicleFinanceInstallment::class,
                columns: [
                    $this->col('agreement', 'Finance Agreement', 'financeAgreement.agreement_number'),
                    $this->col('vehicle', 'Vehicle', 'financeAgreement.vehicle.registration_number'),
                    $this->col('leasing_company', 'Leasing Company', 'financeAgreement.supplier.display_name'),
                    $this->col('installment_number', 'Installment', sort: 'installment_number'),
                    $this->col('due_date', 'Due Date', format: 'date', sort: 'due_date'),
                    $this->money('principal_due', 'Principal'),
                    $this->money('interest_due', 'Interest'),
                    $this->money('total_due', 'Total Due'),
                    $this->money('paid_amount', 'Paid'),
                    $this->money('balance_due', 'Balance'),
                    $this->col('status', 'Status', format: 'enum', sort: 'status'),
                ],
                search: ['financeAgreement.agreement_number', 'financeAgreement.vehicle.registration_number', 'financeAgreement.supplier.name'],
                relations: ['financeAgreement.vehicle', 'financeAgreement.supplier'],
                filters: [$this->filter('status', 'Status', 'status')],
                dateColumn: 'due_date',
                defaultSort: 'due_date',
                orientation: 'landscape',
            ),
            new ReportDefinition(
                key: 'vehicle-rental.profitability',
                title: 'Vehicle Rental Profitability',
                group: 'Vehicle Rental',
                model: RentalVehicleAllocation::class,
                columns: [
                    $this->col('vehicle', 'Vehicle', 'vehicle.registration_number'),
                    $this->col('agreement', 'Customer Agreement', 'agreement.agreement_number'),
                    $this->money('revenue_total', 'Revenue'),
                    $this->money('owner_cost_total', 'Owner Cost'),
                    $this->money('company_cost_total', 'Company Direct Cost'),
                    new ReportColumn('profit', 'Contribution', format: 'money', summarize: true, value: fn (RentalVehicleAllocation $allocation): string => $this->math->sub(
                        (string) ($allocation->revenue_total ?? '0'),
                        $this->math->add((string) ($allocation->owner_cost_total ?? '0'), (string) ($allocation->company_cost_total ?? '0')),
                    )),
                ],
                search: ['vehicle.registration_number', 'agreement.agreement_number'],
                relations: ['vehicle', 'agreement'],
                defaultSort: 'id',
                scope: static fn ($query) => $query
                    ->whereHas('agreement', fn ($agreement) => $agreement->where('agreement_kind', 'customer_rental'))
                    ->select('rental_vehicle_allocations.*')
                    ->selectSub(static fn ($sub) => $sub->from('rental_calculation_lines')
                        ->join('rental_calculation_runs', 'rental_calculation_runs.id', '=', 'rental_calculation_lines.calculation_run_id')
                        ->join('rental_billing_periods', 'rental_billing_periods.id', '=', 'rental_calculation_runs.billing_period_id')
                        ->whereColumn('rental_billing_periods.agreement_id', 'rental_vehicle_allocations.agreement_id')
                        ->where('rental_billing_periods.financial_side', 'revenue')
                        ->where('rental_calculation_runs.calculation_status', 'approved')
                        ->where('rental_calculation_lines.status', 'approved')
                        ->selectRaw('COALESCE(SUM(rental_calculation_lines.total_amount), 0)'), 'revenue_total')
                    ->selectSub(static fn ($sub) => $sub->from('rental_usage_contexts')
                        ->join('rental_calculation_lines', 'rental_calculation_lines.usage_context_id', '=', 'rental_usage_contexts.id')
                        ->join('rental_calculation_runs', 'rental_calculation_runs.id', '=', 'rental_calculation_lines.calculation_run_id')
                        ->whereColumn('rental_usage_contexts.vehicle_allocation_id', 'rental_vehicle_allocations.source_allocation_id')
                        ->where('rental_usage_contexts.financial_side', 'cost')
                        ->where('rental_calculation_runs.calculation_status', 'approved')
                        ->where('rental_calculation_lines.status', 'approved')
                        ->selectRaw('COALESCE(SUM(rental_calculation_lines.total_amount), 0)'), 'owner_cost_total')
                    ->selectSub(static fn ($sub) => $sub->from('rental_expense_allocations')
                        ->whereColumn('rental_expense_allocations.target_vehicle_allocation_id', 'rental_vehicle_allocations.id')
                        ->where('rental_expense_allocations.allocation_type', 'company_cost')
                        ->where('rental_expense_allocations.status', 'approved')
                        ->selectRaw('COALESCE(SUM(rental_expense_allocations.total_amount), 0)'), 'company_cost_total'),
                description: 'Approved rental revenue less approved owner cost and company direct rental costs.',
            ),
            new ReportDefinition(
                key: 'vehicle-rental.tax-traceability',
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
                dateColumn: 'transaction_date',
                defaultSort: 'transaction_date',
                scope: static fn ($query) => $query->where('source_type', 'invoice')
                    ->whereExists(static fn ($sources) => $sources->selectRaw('1')
                        ->from('invoice_sources')
                        ->whereColumn('invoice_sources.invoice_id', 'tax_transactions.source_id')
                        ->where('invoice_sources.source_type', 'rental_calculation_run')),
            ),
        ];
    }

    private function rentalAgreementReport(string $key, string $title): ReportDefinition
    {
        return new ReportDefinition(
            key: $key,
            title: $title,
            group: 'Vehicle Rental',
            model: RentalAgreement::class,
            columns: $this->rentalAgreementColumns(),
            search: ['agreement_number', 'customer.name', 'supplier.name', 'allocations.vehicle.registration_number'],
            relations: ['customer', 'supplier', 'allocations.vehicle'],
            filters: [
                $this->filter('status', 'Status', 'status'),
                $this->filter('agreement_kind', 'Agreement Kind', 'agreement_kind'),
                $this->filter('rental_mode', 'Rental Mode', 'rental_mode'),
            ],
            dateColumn: 'agreement_date',
            defaultSort: 'agreement_date',
            orientation: 'landscape',
        );
    }

    /** @return list<ReportColumn> */
    private function rentalAgreementColumns(): array
    {
        return [
            $this->col('agreement_date', 'Date', format: 'date', sort: 'agreement_date'),
            $this->col('agreement_number', 'Agreement', sort: 'agreement_number'),
            $this->col('agreement_kind', 'Kind', format: 'enum', sort: 'agreement_kind'),
            new ReportColumn('party', 'Customer / Owner', value: static fn (RentalAgreement $agreement): ?string => $agreement->customer?->display_name
                ?? $agreement->customer?->name
                ?? $agreement->supplier?->display_name
                ?? $agreement->supplier?->name),
            new ReportColumn('vehicles', 'Vehicle(s)', value: static fn (RentalAgreement $agreement): string => $agreement->allocations
                ->map(fn ($allocation) => $allocation->vehicle?->registration_number ?? $allocation->vehicle?->vehicle_number)
                ->filter()->unique()->implode(', ')),
            $this->col('starts_at', 'Start', format: 'datetime', sort: 'starts_at'),
            $this->col('ends_at', 'End', format: 'datetime', sort: 'ends_at'),
            $this->col('rental_mode', 'Mode', format: 'enum', sort: 'rental_mode'),
            $this->col('status', 'Status', format: 'enum', sort: 'status'),
        ];
    }

    private function rentalCalculationReport(string $key, string $title, string $side): ReportDefinition
    {
        return new ReportDefinition(
            key: $key,
            title: $title,
            group: 'Vehicle Rental',
            model: RentalCalculationRun::class,
            columns: [
                $this->col('agreement', 'Agreement', 'billingPeriod.agreement.agreement_number'),
                $this->col('period_start', 'Period Start', 'billingPeriod.period_start', 'date'),
                $this->col('period_end', 'Period End', 'billingPeriod.period_end', 'date'),
                $this->col('run_version', 'Version', sort: 'run_version'),
                $this->money('net_total', 'Net'),
                $this->money('tax_total', 'Tax'),
                $this->money('withholding_total', 'Withholding'),
                $this->money('grand_total', 'Total'),
                $this->col('calculation_status', 'Calculation', format: 'enum', sort: 'calculation_status'),
                $this->col('document_status', 'Document', format: 'enum', sort: 'document_status'),
            ],
            search: ['billingPeriod.agreement.agreement_number'],
            relations: ['billingPeriod.agreement'],
            filters: [
                $this->filter('calculation_status', 'Calculation Status', 'calculation_status'),
                $this->filter('document_status', 'Document Status', 'document_status'),
            ],
            dateColumn: 'billingPeriod.period_start',
            defaultSort: 'id',
            scope: static fn ($query) => $query->whereHas('billingPeriod', fn ($period) => $period->where('financial_side', $side)),
            orientation: 'landscape',
        );
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
            scope: static fn ($query) => $query->where('remaining_amount', '>', '0')
                ->whereHas('invoice', fn ($invoice) => $invoice
                    ->where('invoice_type', 'rental')
                    ->where('direction', $direction)
                    ->whereNotIn('status', ['cancelled', 'void'])),
        );
    }

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

    private function voucherPayment(string $key, string $title, string $direction): ReportDefinition
    {
        return new ReportDefinition(
            key: $key,
            title: $title,
            group: 'Vouchers',
            model: Payment::class,
            columns: [
                $this->col('payment_date', 'Date', format: 'date', sort: 'payment_date'),
                $this->col('payment_number', 'Voucher', sort: 'payment_number'),
                $this->col('payee_name', 'Payer / Payee', sort: 'payee_name'),
                $this->col('party_type', 'Party Type', format: 'enum', sort: 'party_type'),
                $this->col('source_type', 'Source', format: 'enum', sort: 'source_type'),
                $this->money('total_amount', 'Amount'),
                $this->money('allocated_amount', 'Allocated'),
                $this->money('unapplied_amount', 'Unallocated'),
                $this->col('document_status', 'Document', format: 'enum', sort: 'document_status'),
                $this->col('allocation_status', 'Allocation', format: 'enum', sort: 'allocation_status'),
                $this->col('posting_status', 'Posting', format: 'enum', sort: 'posting_status'),
                $this->col('instrument_status', 'Instrument', format: 'enum', sort: 'instrument_status'),
            ],
            search: ['payment_number', 'payee_name', 'reference_number', 'source_type'],
            filters: [
                $this->filter('document_status', 'Document Status', 'document_status'),
                $this->filter('allocation_status', 'Allocation Status', 'allocation_status'),
                $this->filter('posting_status', 'Posting Status', 'posting_status'),
                $this->filter('instrument_status', 'Instrument Status', 'instrument_status'),
            ],
            dateColumn: 'payment_date',
            defaultSort: 'payment_date',
            defaultDirection: 'desc',
            scope: static fn ($query) => $query->where('direction', $direction),
            description: 'Voucher presentation register backed by Payment source records.',
            orientation: 'landscape',
        );
    }

    private function voucherPaymentReversal(string $key, string $title): ReportDefinition
    {
        return new ReportDefinition(
            key: $key,
            title: $title,
            group: 'Vouchers',
            model: PaymentReversal::class,
            columns: [
                $this->col('reversal_date', 'Date', format: 'date', sort: 'reversal_date'),
                $this->col('reversal_number', 'Voucher', sort: 'reversal_number'),
                $this->col('payment', 'Original Payment', 'payment.payment_number'),
                $this->money('original_amount', 'Original'),
                $this->money('reversed_amount', 'Reversed'),
                $this->col('reason', 'Reason', sort: 'reason'),
                $this->col('status', 'Status', format: 'enum', sort: 'status'),
            ],
            search: ['reversal_number', 'payment.payment_number', 'reason'],
            relations: ['payment'],
            filters: [$this->filter('status', 'Status', 'status')],
            dateColumn: 'reversal_date',
            defaultSort: 'reversal_date',
            defaultDirection: 'desc',
            description: 'Reversal vouchers backed by Payment reversal records.',
            orientation: 'landscape',
        );
    }

    private function voucherJournal(string $key, string $title, string $journalType): ReportDefinition
    {
        return new ReportDefinition(
            key: $key,
            title: $title,
            group: 'Vouchers',
            model: FinanceJournalEntry::class,
            columns: [
                $this->col('journal_date', 'Date', format: 'date', sort: 'journal_date'),
                $this->col('journal_number', 'Voucher', sort: 'journal_number'),
                $this->col('source_module', 'Source Module', sort: 'source_module'),
                $this->col('source_number', 'Source Number', sort: 'source_number'),
                $this->col('description', 'Narration', sort: 'description'),
                $this->money('total_debit', 'Debit'),
                $this->money('total_credit', 'Credit'),
                $this->col('status', 'Status', format: 'enum', sort: 'status'),
            ],
            search: ['journal_number', 'source_number', 'description'],
            filters: [$this->filter('status', 'Status', 'status')],
            dateColumn: 'journal_date',
            defaultSort: 'journal_date',
            defaultDirection: 'desc',
            scope: static fn ($query) => $query->where('journal_type', $journalType),
            description: 'Voucher register backed by Finance journal source records.',
            orientation: 'landscape',
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
