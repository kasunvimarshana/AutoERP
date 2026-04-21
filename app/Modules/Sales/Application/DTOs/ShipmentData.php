<?php

declare(strict_types=1);

namespace Modules\Sales\Application\DTOs;

class ShipmentData
{
    /**
     * @param  array<string, mixed>|null  $metadata
     * @param  array<int, mixed>|null  $lines
     */
    public function __construct(
        public readonly int $tenantId,
        public readonly int $customerId,
        public readonly int $warehouseId,
        public readonly int $currencyId,
        public readonly ?int $salesOrderId = null,
        public readonly ?string $shipmentNumber = null,
        public readonly string $status = 'draft',
        public readonly ?string $shippedDate = null,
        public readonly ?string $carrier = null,
        public readonly ?string $trackingNumber = null,
        public readonly ?string $notes = null,
        public readonly ?array $metadata = null,
        public readonly ?array $lines = null,
        public readonly ?int $id = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            tenantId: (int) $data['tenant_id'],
            customerId: (int) $data['customer_id'],
            warehouseId: (int) $data['warehouse_id'],
            currencyId: (int) $data['currency_id'],
            salesOrderId: isset($data['sales_order_id']) ? (int) $data['sales_order_id'] : null,
            shipmentNumber: isset($data['shipment_number']) ? (string) $data['shipment_number'] : null,
            status: isset($data['status']) ? (string) $data['status'] : 'draft',
            shippedDate: isset($data['shipped_date']) ? (string) $data['shipped_date'] : null,
            carrier: isset($data['carrier']) ? (string) $data['carrier'] : null,
            trackingNumber: isset($data['tracking_number']) ? (string) $data['tracking_number'] : null,
            notes: isset($data['notes']) ? (string) $data['notes'] : null,
            metadata: isset($data['metadata']) && is_array($data['metadata']) ? $data['metadata'] : null,
            lines: isset($data['lines']) && is_array($data['lines']) ? $data['lines'] : null,
            id: isset($data['id']) ? (int) $data['id'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenantId,
            'customer_id' => $this->customerId,
            'sales_order_id' => $this->salesOrderId,
            'warehouse_id' => $this->warehouseId,
            'shipment_number' => $this->shipmentNumber,
            'status' => $this->status,
            'shipped_date' => $this->shippedDate,
            'carrier' => $this->carrier,
            'tracking_number' => $this->trackingNumber,
            'currency_id' => $this->currencyId,
            'notes' => $this->notes,
            'metadata' => $this->metadata,
        ];
    }
}
