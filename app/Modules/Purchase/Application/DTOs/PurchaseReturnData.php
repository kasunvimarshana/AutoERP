<?php

declare(strict_types=1);

namespace Modules\Purchase\Application\DTOs;

class PurchaseReturnData
{
    public function __construct(
        public readonly int $tenant_id,
        public readonly int $supplier_id,
        public readonly string $return_number,
        public readonly string $return_date,
        public readonly int $currency_id,
        public readonly string $status = 'draft',
        public readonly string $exchange_rate = '1',
        public readonly ?int $original_grn_id = null,
        public readonly ?int $original_invoice_id = null,
        public readonly ?string $return_reason = null,
        public readonly string $subtotal = '0',
        public readonly string $tax_total = '0',
        public readonly string $grand_total = '0',
        public readonly ?string $debit_note_number = null,
        public readonly ?int $journal_entry_id = null,
        public readonly ?string $notes = null,
        public readonly ?array $metadata = null,
        public readonly ?int $id = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            tenant_id: (int) $data['tenant_id'],
            supplier_id: (int) $data['supplier_id'],
            return_number: (string) $data['return_number'],
            return_date: (string) $data['return_date'],
            currency_id: (int) $data['currency_id'],
            status: isset($data['status']) ? (string) $data['status'] : 'draft',
            exchange_rate: isset($data['exchange_rate']) ? (string) $data['exchange_rate'] : '1',
            original_grn_id: isset($data['original_grn_id']) ? (int) $data['original_grn_id'] : null,
            original_invoice_id: isset($data['original_invoice_id']) ? (int) $data['original_invoice_id'] : null,
            return_reason: isset($data['return_reason']) ? (string) $data['return_reason'] : null,
            subtotal: isset($data['subtotal']) ? (string) $data['subtotal'] : '0',
            tax_total: isset($data['tax_total']) ? (string) $data['tax_total'] : '0',
            grand_total: isset($data['grand_total']) ? (string) $data['grand_total'] : '0',
            debit_note_number: isset($data['debit_note_number']) ? (string) $data['debit_note_number'] : null,
            journal_entry_id: isset($data['journal_entry_id']) ? (int) $data['journal_entry_id'] : null,
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
            'return_number' => $this->return_number,
            'return_date' => $this->return_date,
            'currency_id' => $this->currency_id,
            'status' => $this->status,
            'exchange_rate' => $this->exchange_rate,
            'original_grn_id' => $this->original_grn_id,
            'original_invoice_id' => $this->original_invoice_id,
            'return_reason' => $this->return_reason,
            'subtotal' => $this->subtotal,
            'tax_total' => $this->tax_total,
            'grand_total' => $this->grand_total,
            'debit_note_number' => $this->debit_note_number,
            'journal_entry_id' => $this->journal_entry_id,
            'notes' => $this->notes,
            'metadata' => $this->metadata,
        ];
    }
}
