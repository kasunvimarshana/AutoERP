<?php

declare(strict_types=1);

namespace Modules\Purchase\Application\DTOs;

class PurchaseOrderData
{
    public function __construct(
        public readonly int $tenantId,
        public readonly int $supplierId,
        public readonly int $warehouseId,
        public readonly string $poNumber,
        public readonly int $currencyId,
        public readonly string $orderDate,
        public readonly int $createdBy,
        public readonly string $exchangeRate = '1',
        public readonly string $status = 'draft',
        public readonly ?string $expectedDate = null,
        public readonly ?int $orgUnitId = null,
        public readonly string $subtotal = '0',
        public readonly string $taxTotal = '0',
        public readonly string $discountTotal = '0',
        public readonly string $grandTotal = '0',
        public readonly ?string $notes = null,
        public readonly ?array $metadata = null,
        public readonly ?int $approvedBy = null,
        public readonly ?int $id = null,
    )
    {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            tenantId: (int) $data['tenant_id'],
            supplierId: (int) $data['supplier_id'],
            warehouseId: (int) $data['warehouse_id'],
            poNumber: (string) $data['po_number'],
            currencyId: (int) $data['currency_id'],
            orderDate: (string) $data['order_date'],
            createdBy: (int) $data['created_by'],
            exchangeRate: isset($data['exchange_rate']) ? (string) $data['exchange_rate'] : '1',
            status: isset($data['status']) ? (string) $data['status'] : 'draft',
            expectedDate: isset($data['expected_date']) ? (string) $data['expected_date'] : null,
            orgUnitId: isset($data['org_unit_id']) ? (int) $data['org_unit_id'] : null,
            subtotal: isset($data['subtotal']) ? (string) $data['subtotal'] : '0',
            taxTotal: isset($data['tax_total']) ? (string) $data['tax_total'] : '0',
            discountTotal: isset($data['discount_total']) ? (string) $data['discount_total'] : '0',
            grandTotal: isset($data['grand_total']) ? (string) $data['grand_total'] : '0',
            notes: isset($data['notes']) ? (string) $data['notes'] : null,
            metadata: isset($data['metadata']) ? (array) $data['metadata'] : null,
            approvedBy: isset($data['approved_by']) ? (int) $data['approved_by'] : null,
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
            'po_number' => $this->poNumber,
            'currency_id' => $this->currencyId,
            'order_date' => $this->orderDate,
            'created_by' => $this->createdBy,
            'exchange_rate' => $this->exchangeRate,
            'status' => $this->status,
            'expected_date' => $this->expectedDate,
            'org_unit_id' => $this->orgUnitId,
            'subtotal' => $this->subtotal,
            'tax_total' => $this->taxTotal,
            'discount_total' => $this->discountTotal,
            'grand_total' => $this->grandTotal,
            'notes' => $this->notes,
            'metadata' => $this->metadata,
            'approved_by' => $this->approvedBy,
        ];
    }
}
