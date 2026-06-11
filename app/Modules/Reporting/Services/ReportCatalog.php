<?php

declare(strict_types=1);

namespace Modules\Reporting\Services;

use InvalidArgumentException;
use Modules\Customer\Models\Customer;
use Modules\Finance\Models\FinanceAccountBalance;
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
use Modules\UOM\Models\UnitOfMeasureModel;
use Modules\Vehicle\Models\Vehicle;
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
                $this->qty('quantity_allocated', 'Allocated'), $this->qty('quantity_available', 'Available'), $this->money('total_value', 'Value'),
            ], ['item.code', 'item.name', 'warehouse.name', 'batch.batch_number'], ['item', 'item.baseUom', 'warehouse', 'warehouseLocation', 'batch']),
            $this->inventory('inventory.stock-movement', 'Stock Movement', InventoryMovement::class, [
                $this->col('movement_date', 'Date', format: 'date', sort: 'movement_date'), $this->itemCol(), $this->col('warehouse', 'Warehouse', 'warehouse.name'),
                $this->col('movement_type', 'Type', format: 'enum', sort: 'movement_type'), $this->col('direction', 'Direction', format: 'enum', sort: 'direction'),
                $this->qty('quantity', 'Quantity'), $this->col('uom', 'UOM', 'baseUom.code'), $this->money('total_cost', 'Cost'), $this->col('source_type', 'Source', sort: 'source_type'), $this->col('status', 'Status', format: 'enum', sort: 'status'),
            ], ['item.code', 'item.name', 'warehouse.name', 'source_type', 'reference_number'], ['item', 'baseUom', 'warehouse'], 'movement_date'),
            $this->inventory('inventory.batch-lot', 'Batch/Lot', InventoryBatch::class, [
                $this->col('batch_number', 'Batch', sort: 'batch_number'), $this->itemCol(), $this->col('manufacture_date', 'Manufactured', format: 'date', sort: 'manufacture_date'),
                $this->col('expiry_date', 'Expiry', format: 'date', sort: 'expiry_date'), $this->col('status', 'Status', format: 'enum', sort: 'status'),
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

            $this->invoice('invoice.register', 'Invoice Register', Invoice::class, 'invoice_date', 'invoice_number', 'grand_total'),
            $this->definition('invoice.balance', 'Invoice Balance', 'Invoice & Payment', InvoiceBalance::class, [
                $this->col('invoice', 'Invoice', 'invoice.invoice_number'), $this->money('invoice_total', 'Invoice Total'), $this->money('paid_amount', 'Paid'),
                $this->money('credit_allocated_amount', 'Credits'), $this->money('remaining_amount', 'Remaining'), $this->col('status', 'Status', format: 'enum', sort: 'status'),
            ], ['invoice.invoice_number'], ['invoice']),
            $this->payment('payment.register', 'Payment Register', Payment::class, 'payment_date', 'payment_number', 'total_amount'),
            $this->definition('payment.allocation', 'Payment Allocation', 'Invoice & Payment', PaymentAllocation::class, [
                $this->col('allocation_date', 'Date', format: 'date', sort: 'allocation_date'), $this->col('payment', 'Payment', 'payment.payment_number'),
                $this->money('invoice_total', 'Invoice Total'), $this->money('allocated_amount', 'Allocated'), $this->money('invoice_balance_after', 'Balance After'), $this->col('status', 'Status', format: 'enum', sort: 'status'),
            ], ['payment.payment_number'], ['payment'], 'allocation_date'),
            $this->definition('payment.unapplied', 'Unapplied Payments', 'Invoice & Payment', PaymentUnappliedBalance::class, [
                $this->col('payment', 'Payment', 'payment.payment_number'), $this->money('original_amount', 'Original'), $this->money('allocated_amount', 'Allocated'),
                $this->money('refunded_amount', 'Refunded'), $this->money('remaining_amount', 'Remaining'), $this->col('status', 'Status', format: 'enum', sort: 'status'),
            ], ['payment.payment_number'], ['payment']),

            $this->definition('finance.account-balances', 'Account Balances', 'Finance', FinanceAccountBalance::class, [
                $this->col('account', 'Account', 'account.code'), $this->col('account_name', 'Account Name', 'account.name'), $this->money('opening_debit', 'Opening Dr'),
                $this->money('opening_credit', 'Opening Cr'), $this->money('period_debit', 'Period Dr'), $this->money('period_credit', 'Period Cr'),
                $this->money('closing_debit', 'Closing Dr'), $this->money('closing_credit', 'Closing Cr'),
            ], ['account.code', 'account.name'], ['account', 'fiscalPeriod']),
            $this->definition('finance.journals', 'Journal Entries', 'Finance', FinanceJournalEntry::class, [
                $this->col('journal_date', 'Date', format: 'date', sort: 'journal_date'), $this->col('journal_number', 'Journal', sort: 'journal_number'),
                $this->col('journal_type', 'Type', format: 'enum', sort: 'journal_type'), $this->money('total_debit', 'Debit'), $this->money('total_credit', 'Credit'), $this->col('status', 'Status', format: 'enum', sort: 'status'),
            ], ['journal_number', 'description'], [], 'journal_date'),
            $this->definition('finance.ledger', 'Ledger', 'Finance', FinanceLedgerEntry::class, [
                $this->col('entry_date', 'Date', format: 'date', sort: 'entry_date'), $this->col('account', 'Account', 'account.code'), $this->col('account_name', 'Account Name', 'account.name'),
                $this->col('journal', 'Journal', 'journalEntry.journal_number'), $this->money('debit', 'Debit'), $this->money('credit', 'Credit'), $this->money('balance_after', 'Balance'),
            ], ['account.code', 'account.name', 'journalEntry.journal_number'], ['account', 'journalEntry'], 'entry_date'),
            $this->definition('finance.trial-balance', 'Trial Balance', 'Finance', FinanceAccountBalance::class, [
                $this->col('account', 'Account', 'account.code'), $this->col('account_name', 'Account Name', 'account.name'), $this->money('closing_debit', 'Debit'), $this->money('closing_credit', 'Credit'),
            ], ['account.code', 'account.name'], ['account']),
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
            defaultSort: $dateColumn ?? 'id',
            includeGlobalOrganization: $includeGlobalOrganization,
            constraints: $constraints,
        );
    }

    private function masters(string $key, string $title, string $model, array $columns, array $search, array $relations = [], ?string $dateColumn = null): ReportDefinition
    {
        return $this->definition($key, $title, 'Masters', $model, $columns, $search, $relations, $dateColumn, true);
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
        return $this->definition($key, $title, 'Invoice & Payment', $model, [
            $this->col($dateColumn, 'Date', format: 'date', sort: $dateColumn), $this->col($numberColumn, 'Number', sort: $numberColumn), $this->col('party_name', 'Party', sort: 'party_name'),
            $this->money($amountColumn, 'Amount'), $this->money('allocated_amount', 'Allocated'), $this->money('unapplied_amount', 'Unapplied'), $this->col('status', 'Status', format: 'enum', sort: 'status'),
        ], [$numberColumn, 'party_name'], [], $dateColumn);
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
