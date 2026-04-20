<?php

declare(strict_types=1);

namespace Modules\Purchase\Application\DTOs;

class PurchaseInvoiceData
{
    public function __construct(
        public readonly int $tenant_id,
        public readonly int $supplier_id,
        public readonly string $invoice_number,
        public readonly string $invoice_date,
        public readonly string $due_date,
        public readonly int $currency_id,
        public readonly string $status = 'draft',
        public readonly string $exchange_rate = '1',
        public readonly ?int $grn_header_id = null,
        public readonly ?int $purchase_order_id = null,
        public readonly ?string $supplier_invoice_number = null,
        public readonly string $subtotal = '0',
        public readonly string $tax_total = '0',
        public readonly string $discount_total = '0',
        public readonly string $grand_total = '0',
        public readonly ?int $ap_account_id = null,
        public readonly ?int $journal_entry_id = null,
        public readonly ?int $id = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            tenant_id: (int) $data['tenant_id'],
            supplier_id: (int) $data['supplier_id'],
            invoice_number: (string) $data['invoice_number'],
            invoice_date: (string) $data['invoice_date'],
            due_date: (string) $data['due_date'],
            currency_id: (int) $data['currency_id'],
            status: isset($data['status']) ? (string) $data['status'] : 'draft',
            exchange_rate: isset($data['exchange_rate']) ? (string) $data['exchange_rate'] : '1',
            grn_header_id: isset($data['grn_header_id']) ? (int) $data['grn_header_id'] : null,
            purchase_order_id: isset($data['purchase_order_id']) ? (int) $data['purchase_order_id'] : null,
            supplier_invoice_number: isset($data['supplier_invoice_number']) ? (string) $data['supplier_invoice_number'] : null,
            subtotal: isset($data['subtotal']) ? (string) $data['subtotal'] : '0',
            tax_total: isset($data['tax_total']) ? (string) $data['tax_total'] : '0',
            discount_total: isset($data['discount_total']) ? (string) $data['discount_total'] : '0',
            grand_total: isset($data['grand_total']) ? (string) $data['grand_total'] : '0',
            ap_account_id: isset($data['ap_account_id']) ? (int) $data['ap_account_id'] : null,
            journal_entry_id: isset($data['journal_entry_id']) ? (int) $data['journal_entry_id'] : null,
            id: isset($data['id']) ? (int) $data['id'] : null,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'supplier_id' => $this->supplier_id,
            'invoice_number' => $this->invoice_number,
            'invoice_date' => $this->invoice_date,
            'due_date' => $this->due_date,
            'currency_id' => $this->currency_id,
            'status' => $this->status,
            'exchange_rate' => $this->exchange_rate,
            'grn_header_id' => $this->grn_header_id,
            'purchase_order_id' => $this->purchase_order_id,
            'supplier_invoice_number' => $this->supplier_invoice_number,
            'subtotal' => $this->subtotal,
            'tax_total' => $this->tax_total,
            'discount_total' => $this->discount_total,
            'grand_total' => $this->grand_total,
            'ap_account_id' => $this->ap_account_id,
            'journal_entry_id' => $this->journal_entry_id,
        ];
    }
}
