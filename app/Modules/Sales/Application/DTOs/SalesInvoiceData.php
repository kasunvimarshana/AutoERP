<?php

declare(strict_types=1);

namespace Modules\Sales\Application\DTOs;

class SalesInvoiceData
{
    /**
     * @param  array<string, mixed>|null  $metadata
     * @param  array<int, mixed>|null  $lines
     */
    public function __construct(
        public readonly int $tenantId,
        public readonly int $customerId,
        public readonly int $currencyId,
        public readonly ?int $salesOrderId = null,
        public readonly ?int $shipmentId = null,
        public readonly ?string $invoiceNumber = null,
        public readonly string $status = 'draft',
        public readonly ?string $invoiceDate = null,
        public readonly ?string $dueDate = null,
        public readonly string $exchangeRate = '1.000000',
        public readonly string $subtotal = '0.000000',
        public readonly string $taxTotal = '0.000000',
        public readonly string $discountTotal = '0.000000',
        public readonly string $grandTotal = '0.000000',
        public readonly ?int $arAccountId = null,
        public readonly ?int $journalEntryId = null,
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
            currencyId: (int) $data['currency_id'],
            salesOrderId: isset($data['sales_order_id']) ? (int) $data['sales_order_id'] : null,
            shipmentId: isset($data['shipment_id']) ? (int) $data['shipment_id'] : null,
            invoiceNumber: isset($data['invoice_number']) ? (string) $data['invoice_number'] : null,
            status: isset($data['status']) ? (string) $data['status'] : 'draft',
            invoiceDate: isset($data['invoice_date']) ? (string) $data['invoice_date'] : null,
            dueDate: isset($data['due_date']) ? (string) $data['due_date'] : null,
            exchangeRate: isset($data['exchange_rate']) ? (string) $data['exchange_rate'] : '1.000000',
            subtotal: isset($data['subtotal']) ? (string) $data['subtotal'] : '0.000000',
            taxTotal: isset($data['tax_total']) ? (string) $data['tax_total'] : '0.000000',
            discountTotal: isset($data['discount_total']) ? (string) $data['discount_total'] : '0.000000',
            grandTotal: isset($data['grand_total']) ? (string) $data['grand_total'] : '0.000000',
            arAccountId: isset($data['ar_account_id']) ? (int) $data['ar_account_id'] : null,
            journalEntryId: isset($data['journal_entry_id']) ? (int) $data['journal_entry_id'] : null,
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
            'shipment_id' => $this->shipmentId,
            'invoice_number' => $this->invoiceNumber,
            'status' => $this->status,
            'invoice_date' => $this->invoiceDate,
            'due_date' => $this->dueDate,
            'currency_id' => $this->currencyId,
            'exchange_rate' => $this->exchangeRate,
            'subtotal' => $this->subtotal,
            'tax_total' => $this->taxTotal,
            'discount_total' => $this->discountTotal,
            'grand_total' => $this->grandTotal,
            'ar_account_id' => $this->arAccountId,
            'journal_entry_id' => $this->journalEntryId,
            'notes' => $this->notes,
            'metadata' => $this->metadata,
        ];
    }
}
