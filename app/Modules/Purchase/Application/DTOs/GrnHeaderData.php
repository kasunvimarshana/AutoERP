<?php

declare(strict_types=1);

namespace Modules\Purchase\Application\DTOs;

class GrnHeaderData
{
    public function __construct(
        public readonly int $tenant_id,
        public readonly int $supplier_id,
        public readonly int $warehouse_id,
        public readonly string $grn_number,
        public readonly string $received_date,
        public readonly int $currency_id,
        public readonly int $created_by,
        public readonly string $status = 'draft',
        public readonly string $exchange_rate = '1',
        public readonly ?int $purchase_order_id = null,
        public readonly ?string $notes = null,
        public readonly ?array $metadata = null,
        public readonly ?int $id = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            tenant_id: (int) $data['tenant_id'],
            supplier_id: (int) $data['supplier_id'],
            warehouse_id: (int) $data['warehouse_id'],
            grn_number: (string) $data['grn_number'],
            received_date: (string) $data['received_date'],
            currency_id: (int) $data['currency_id'],
            created_by: (int) $data['created_by'],
            status: isset($data['status']) ? (string) $data['status'] : 'draft',
            exchange_rate: isset($data['exchange_rate']) ? (string) $data['exchange_rate'] : '1',
            purchase_order_id: isset($data['purchase_order_id']) ? (int) $data['purchase_order_id'] : null,
            notes: isset($data['notes']) ? (string) $data['notes'] : null,
            metadata: isset($data['metadata']) ? (array) $data['metadata'] : null,
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
            'grn_number' => $this->grn_number,
            'received_date' => $this->received_date,
            'currency_id' => $this->currency_id,
            'created_by' => $this->created_by,
            'status' => $this->status,
            'exchange_rate' => $this->exchange_rate,
            'purchase_order_id' => $this->purchase_order_id,
            'notes' => $this->notes,
            'metadata' => $this->metadata,
        ];
    }
}
