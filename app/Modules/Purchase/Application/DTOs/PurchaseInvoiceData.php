<?php

declare(strict_types=1);

namespace Modules\Purchase\Application\DTOs;

class PurchaseInvoiceData
{
    public function __construct(
        public readonly int $tenantId,
        public readonly int $supplierId,
        public readonly string $invoiceNumber,
        public readonly string $invoiceDate,
        public readonly string $dueDate,
        public readonly int $currencyId,
        public readonly string $status = 'draft',
        public readonly string $exchangeRate = '1',
        public readonly ?int $grnHeaderId = null,
        public readonly ?int $purchaseOrderId = null,
        public readonly ?string $supplierInvoiceNumber = null,
        public readonly string $subtotal = '0',
        public readonly string $taxTotal = '0',
        public readonly string $discountTotal = '0',
        public readonly string $grandTotal = '0',
        public readonly ?int $apAccountId = null,
        public readonly ?int $journalEntryId = null,
        public readonly ?int $id = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            tenantId: (int) $data['tenant_id'],
            supplierId: (int) $data['supplier_id'],
            invoiceNumber: (string) $data['invoice_number'],
            invoiceDate: (string) $data['invoice_date'],
            dueDate: (string) $data['due_date'],
            currencyId: (int) $data['currency_id'],
            status: isset($data['status']) ? (string) $data['status'] : 'draft',
            exchangeRate: isset($data['exchange_rate']) ? (string) $data['exchange_rate'] : '1',
            grnHeaderId: isset($data['grn_header_id']) ? (int) $data['grn_header_id'] : null,
            purchaseOrderId: isset($data['purchase_order_id']) ? (int) $data['purchase_order_id'] : null,
            supplierInvoiceNumber: isset($data['supplier_invoice_number']) ? (string) $data['supplier_invoice_number'] : null,
            subtotal: isset($data['subtotal']) ? (string) $data['subtotal'] : '0',
            taxTotal: isset($data['tax_total']) ? (string) $data['tax_total'] : '0',
            discountTotal: isset($data['discount_total']) ? (string) $data['discount_total'] : '0',
            grandTotal: isset($data['grand_total']) ? (string) $data['grand_total'] : '0',
            apAccountId: isset($data['ap_account_id']) ? (int) $data['ap_account_id'] : null,
            journalEntryId: isset($data['journal_entry_id']) ? (int) $data['journal_entry_id'] : null,
            id: isset($data['id']) ? (int) $data['id'] : null,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenantId,
            'supplier_id' => $this->supplierId,
            'invoice_number' => $this->invoiceNumber,
            'invoice_date' => $this->invoiceDate,
            'due_date' => $this->dueDate,
            'currency_id' => $this->currencyId,
            'status' => $this->status,
            'exchange_rate' => $this->exchangeRate,
            'grn_header_id' => $this->grnHeaderId,
            'purchase_order_id' => $this->purchaseOrderId,
            'supplier_invoice_number' => $this->supplierInvoiceNumber,
            'subtotal' => $this->subtotal,
            'tax_total' => $this->taxTotal,
            'discount_total' => $this->discountTotal,
            'grand_total' => $this->grandTotal,
            'ap_account_id' => $this->apAccountId,
            'journal_entry_id' => $this->journalEntryId,
        ];
    }
}
