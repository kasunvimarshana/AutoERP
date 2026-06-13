<?php

declare(strict_types=1);

namespace Modules\Sales\Services;

use Modules\Sales\DTOs\CreateSalesOrderData;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Models\SalesOrderLine;

/**
 * Stable sales-order API used by controllers and module integrations.
 */
final class SalesOrderService
{
    public function __construct(
        private readonly SalesOrderWriteService $writes,
        private readonly SalesOrderStatusService $statuses,
        private readonly SalesOrderQuantityService $quantities,
    ) {}

    public function create(CreateSalesOrderData $data): SalesOrder
    {
        return $this->load($this->writes->create($data));
    }

    public function update(SalesOrder $order, CreateSalesOrderData $data): SalesOrder
    {
        return $this->load($this->writes->update($order, $data));
    }

    public function delete(SalesOrder $order): void
    {
        $this->writes->delete($order);
    }

    public function submit(SalesOrder $order, ?int $userId = null): SalesOrder
    {
        return $this->load($this->statuses->submit($order, $userId));
    }

    public function approve(SalesOrder $order, ?int $userId = null): SalesOrder
    {
        return $this->load($this->statuses->approve($order, $userId));
    }

    public function cancel(SalesOrder $order, ?int $userId = null): SalesOrder
    {
        return $this->load($this->statuses->cancel($order, $userId));
    }

    public function close(SalesOrder $order, ?int $userId = null): SalesOrder
    {
        return $this->load($this->statuses->close($order, $userId));
    }

    public function applyAllocated(SalesOrderLine $line, string $quantity, ?int $allocationId): void
    {
        $this->quantities->applyAllocated($line, $quantity, $allocationId);
    }

    public function applyDelivered(SalesOrderLine $line, string $quantity): void
    {
        $this->quantities->applyDelivered($line, $quantity);
    }

    public function reverseDelivered(SalesOrderLine $line, string $quantity): void
    {
        $this->quantities->reverseDelivered($line, $quantity);
    }

    public function applyInvoiced(SalesOrderLine $line, string $quantity): void
    {
        $this->quantities->applyInvoiced($line, $quantity);
    }

    public function applyReturned(SalesOrderLine $line, string $quantity): void
    {
        $this->quantities->applyReturned($line, $quantity);
    }

    private function load(SalesOrder $order): SalesOrder
    {
        return $order->refresh()->load([
            'customer.creditProfile',
            'quotation',
            'warehouse',
            'warehouseLocation',
            'currency',
            'lines.item',
            'lines.variant',
            'lines.orderedUom',
            'lines.baseUom',
            'adjustments',
        ]);
    }
}
