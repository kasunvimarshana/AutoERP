<?php

declare(strict_types=1);

namespace Modules\Purchase\Services;

use Modules\Purchase\DTOs\CreatePurchaseOrderData;
use Modules\Purchase\Models\PurchaseOrder;
use Modules\Purchase\Models\PurchaseOrderLine;

/**
 * Stable Purchase Order API used by controllers and module integrations.
 */
final class PurchaseOrderService
{
    public function __construct(
        private readonly PurchaseOrderWriteService $writes,
        private readonly PurchaseOrderStatusService $statuses,
        private readonly PurchaseOrderQuantityService $quantities,
    ) {}

    public function create(CreatePurchaseOrderData $data): PurchaseOrder
    {
        return $this->loadOrder($this->writes->create($data));
    }

    public function update(PurchaseOrder $order, CreatePurchaseOrderData $data, ?int $expectedVersion = null): PurchaseOrder
    {
        return $this->loadOrder($this->writes->update($order, $data, $expectedVersion));
    }

    public function delete(PurchaseOrder $order, ?int $expectedVersion = null): void
    {
        $this->writes->delete($order, $expectedVersion);
    }

    public function submit(PurchaseOrder $order, ?int $submittedBy = null, ?int $expectedVersion = null): PurchaseOrder
    {
        return $this->loadOrder($this->statuses->submit($order, $submittedBy, $expectedVersion));
    }

    public function approve(PurchaseOrder $order, ?int $approvedBy = null, ?int $expectedVersion = null): PurchaseOrder
    {
        return $this->loadOrder($this->statuses->approve($order, $approvedBy, $expectedVersion));
    }

    public function cancel(PurchaseOrder $order, ?int $expectedVersion = null): PurchaseOrder
    {
        return $this->loadOrder($this->statuses->cancel($order, $expectedVersion));
    }

    public function close(PurchaseOrder $order, ?int $closedBy = null, ?int $expectedVersion = null): PurchaseOrder
    {
        return $this->loadOrder($this->statuses->close($order, $closedBy, $expectedVersion));
    }

    public function applyReceived(PurchaseOrderLine $line, string $quantity): void
    {
        $this->quantities->applyReceived($line, $quantity);
    }

    public function applyInvoiced(PurchaseOrderLine $line, string $quantity): void
    {
        $this->quantities->applyInvoiced($line, $quantity);
    }

    public function applyReturned(PurchaseOrderLine $line, string $quantity): void
    {
        $this->quantities->applyReturned($line, $quantity);
    }

    public function reverseReceived(PurchaseOrderLine $line, string $quantity): void
    {
        $this->quantities->reverseReceived($line, $quantity);
    }

    private function loadOrder(PurchaseOrder $order): PurchaseOrder
    {
        $order = $order->refresh()->load([
            'supplier',
            'warehouse',
            'warehouseLocation',
            'currency',
            'createdBy',
            'approvedBy',
            'closedBy',
            'lines.item',
            'lines.variant',
            'lines.uom',
            'adjustments',
        ]);
        $order->loadSum('lines as received_quantity', 'received_quantity');
        $order->loadSum('lines as invoiced_quantity', 'invoiced_quantity');
        $order->loadSum('lines as returned_quantity', 'returned_quantity');

        return $order;
    }
}
