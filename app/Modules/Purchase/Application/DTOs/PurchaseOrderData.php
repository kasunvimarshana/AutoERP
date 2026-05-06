<?php

declare(strict_types=1);

namespace Modules\Purchase\Application\DTOs;

class PurchaseOrderData
{
    public function __construct(
        public readonly int $tenant_id,
        public readonly int $supplier_id,
        public readonly int $warehouse_id,
        public readonly string $po_number,
        public readonly int $currency_id,
        public readonly string $order_date,
        public readonly int $created_by,
        public readonly string $exchange_rate = '1',
        public readonly string $status = 'draft',
        public readonly ?string $expected_date = null,
        public readonly ?int $org_unit_id = null,
        public readonly string $subtotal = '0',
        public readonly string $tax_total = '0',
        public readonly string $discount_total = '0',
        public readonly string $grand_total = '0',
        public readonly ?string $notes = null,
        public readonly ?array $metadata = null,
        public readonly ?int $approved_by = null,
        public readonly ?int $id = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            tenant_id: (int) $data['tenant_id'],
            supplier_id: (int) $data['supplier_id'],
            warehouse_id: (int) $data['warehouse_id'],
            po_number: (string) $data['po_number'],
            currency_id: (int) $data['currency_id'],
            order_date: (string) $data['order_date'],
            created_by: (int) $data['created_by'],
            exchange_rate: isset($data['exchange_rate']) ? (string) $data['exchange_rate'] : '1',
            status: isset($data['status']) ? (string) $data['status'] : 'draft',
            expected_date: isset($data['expected_date']) ? (string) $data['expected_date'] : null,
            org_unit_id: isset($data['org_unit_id']) ? (int) $data['org_unit_id'] : null,
            subtotal: isset($data['subtotal']) ? (string) $data['subtotal'] : '0',
            tax_total: isset($data['tax_total']) ? (string) $data['tax_total'] : '0',
            discount_total: isset($data['discount_total']) ? (string) $data['discount_total'] : '0',
            grand_total: isset($data['grand_total']) ? (string) $data['grand_total'] : '0',
            notes: isset($data['notes']) ? (string) $data['notes'] : null,
            metadata: isset($data['metadata']) ? (array) $data['metadata'] : null,
            approved_by: isset($data['approved_by']) ? (int) $data['approved_by'] : null,
            id: isset($data['id']) ? (int) $data['id'] : null,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'supplier_id' => $this->supplier_id,
            'warehouse_id' => $this->warehouse_id,
            'po_number' => $this->po_number,
            'currency_id' => $this->currency_id,
            'order_date' => $this->order_date,
            'created_by' => $this->created_by,
            'exchange_rate' => $this->exchange_rate,
            'status' => $this->status,
            'expected_date' => $this->expected_date,
            'org_unit_id' => $this->org_unit_id,
            'subtotal' => $this->subtotal,
            'tax_total' => $this->tax_total,
            'discount_total' => $this->discount_total,
            'grand_total' => $this->grand_total,
            'notes' => $this->notes,
            'metadata' => $this->metadata,
            'approved_by' => $this->approved_by,
        ];
    }
}
