<?php

declare(strict_types=1);

namespace Modules\Purchase\Application\DTOs;

class GrnHeaderData
{
    public function __construct(
        public readonly int $tenantId,
        public readonly int $supplierId,
        public readonly int $warehouseId,
        public readonly string $grnNumber,
        public readonly string $receivedDate,
        public readonly int $currencyId,
        public readonly int $createdBy,
        public readonly string $status = 'draft',
        public readonly string $exchangeRate = '1',
        public readonly ?int $purchaseOrderId = null,
        public readonly ?string $notes = null,
        public readonly ?array $metadata = null,
        public readonly ?int $id = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            tenantId: (int) $data['tenant_id'],
            supplierId: (int) $data['supplier_id'],
            warehouseId: (int) $data['warehouse_id'],
            grnNumber: (string) $data['grn_number'],
            receivedDate: (string) $data['received_date'],
            currencyId: (int) $data['currency_id'],
            createdBy: (int) $data['created_by'],
            status: isset($data['status']) ? (string) $data['status'] : 'draft',
            exchangeRate: isset($data['exchange_rate']) ? (string) $data['exchange_rate'] : '1',
            purchaseOrderId: isset($data['purchase_order_id']) ? (int) $data['purchase_order_id'] : null,
            notes: isset($data['notes']) ? (string) $data['notes'] : null,
            metadata: isset($data['metadata']) ? (array) $data['metadata'] : null,
            id: isset($data['id']) ? (int) $data['id'] : null,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenantId,
            'supplier_id' => $this->supplierId,
            'warehouse_id' => $this->warehouseId,
            'grn_number' => $this->grnNumber,
            'received_date' => $this->receivedDate,
            'currency_id' => $this->currencyId,
            'created_by' => $this->createdBy,
            'status' => $this->status,
            'exchange_rate' => $this->exchangeRate,
            'purchase_order_id' => $this->purchaseOrderId,
            'notes' => $this->notes,
            'metadata' => $this->metadata,
        ];
    }
}
