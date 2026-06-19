<?php

declare(strict_types=1);

namespace Modules\Sales\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Invoice\Enums\InvoiceDirection;
use Modules\Invoice\Models\Invoice;
use Modules\Sales\Enums\SalesDeliveryStatus;
use Modules\Sales\Enums\SalesOrderStatus;
use Modules\Sales\Models\SalesDelivery;
use Modules\Sales\Models\SalesOrder;

final class SalesSourceEligibilityService
{
    public function __construct(private readonly SalesFulfilmentBalanceService $balances) {}

    public function allocatableSalesOrders(int $tenantId, ?int $organizationUnitId, int $perPage): LengthAwarePaginator
    {
        return $this->salesOrderBase($tenantId, $organizationUnitId)
            ->whereHas('lines', fn ($query) => $this->balances->whereSalesOrderLineAllocatable($query))
            ->latest('sales_order_date')
            ->paginate($perPage);
    }

    public function deliverableSalesOrders(int $tenantId, ?int $organizationUnitId, int $perPage): LengthAwarePaginator
    {
        return $this->salesOrderBase($tenantId, $organizationUnitId)
            ->whereHas('lines', fn ($query) => $this->balances->whereSalesOrderLineDeliverable($query))
            ->latest('sales_order_date')
            ->paginate($perPage);
    }

    public function invoiceableSalesOrders(int $tenantId, ?int $organizationUnitId, int $perPage): LengthAwarePaginator
    {
        return $this->salesOrderBase($tenantId, $organizationUnitId)
            ->whereHas('lines', fn ($query) => $this->balances->whereSalesOrderLineInvoiceable($query))
            ->latest('sales_order_date')
            ->paginate($perPage);
    }

    public function invoiceableSalesDeliveries(int $tenantId, ?int $organizationUnitId, int $perPage): LengthAwarePaginator
    {
        return $this->deliveryBase($tenantId, $organizationUnitId)
            ->whereHas('lines', fn ($query) => $this->balances->whereSalesDeliveryLineInvoiceable($query))
            ->latest('delivery_date')
            ->paginate($perPage);
    }

    public function returnableSalesDeliveries(int $tenantId, ?int $organizationUnitId, int $perPage): LengthAwarePaginator
    {
        return $this->deliveryBase($tenantId, $organizationUnitId)
            ->whereHas('lines', fn ($query) => $this->balances->whereSalesDeliveryLineReturnable($query))
            ->latest('delivery_date')
            ->paginate($perPage);
    }

    public function outstandingCustomerInvoices(int $tenantId, ?int $organizationUnitId, int $perPage): LengthAwarePaginator
    {
        return Invoice::query()
            ->where('tenant_id', $tenantId)
            ->when($organizationUnitId === null, fn ($query) => $query->whereNull('organization_unit_id'))
            ->when($organizationUnitId !== null, fn ($query) => $query->where('organization_unit_id', $organizationUnitId))
            ->where('direction', InvoiceDirection::Outbound)
            ->whereRaw('balance_due > 0')
            ->with(['customer', 'currency'])
            ->latest('invoice_date')
            ->paginate($perPage);
    }

    private function salesOrderBase(int $tenantId, ?int $organizationUnitId)
    {
        return SalesOrder::query()
            ->where('tenant_id', $tenantId)
            ->when($organizationUnitId === null, fn ($query) => $query->whereNull('organization_unit_id'))
            ->when($organizationUnitId !== null, fn ($query) => $query->where('organization_unit_id', $organizationUnitId))
            ->where('status', SalesOrderStatus::Approved)
            ->with(['customer', 'warehouse', 'warehouseLocation', 'currency', 'lines.item', 'lines.variant', 'lines.orderedUom', 'lines.baseUom']);
    }

    private function deliveryBase(int $tenantId, ?int $organizationUnitId)
    {
        return SalesDelivery::query()
            ->where('tenant_id', $tenantId)
            ->when($organizationUnitId === null, fn ($query) => $query->whereNull('organization_unit_id'))
            ->when($organizationUnitId !== null, fn ($query) => $query->where('organization_unit_id', $organizationUnitId))
            ->where('status', SalesDeliveryStatus::Posted)
            ->with(['salesOrder', 'customer', 'warehouse', 'warehouseLocation', 'lines.item', 'lines.variant', 'lines.uom']);
    }
}
