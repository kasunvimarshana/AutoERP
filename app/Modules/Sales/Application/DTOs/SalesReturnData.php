<?php

declare(strict_types=1);

namespace Modules\Sales\Application\DTOs;

class SalesReturnData
{
    /**
     * @param  array<string, mixed>|null  $metadata
     * @param  array<int, mixed>|null  $lines
     */
    public function __construct(
        public readonly int $tenantId,
        public readonly int $customerId,
        public readonly int $currencyId,
        public readonly ?int $originalSalesOrderId = null,
        public readonly ?int $originalInvoiceId = null,
        public readonly ?string $returnNumber = null,
        public readonly string $status = 'draft',
        public readonly ?string $returnDate = null,
        public readonly ?string $returnReason = null,
        public readonly string $exchangeRate = '1.000000',
        public readonly string $subtotal = '0.000000',
        public readonly string $taxTotal = '0.000000',
        public readonly string $restockingFeeTotal = '0.000000',
        public readonly string $grandTotal = '0.000000',
        public readonly ?string $creditMemoNumber = null,
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
            originalSalesOrderId: isset($data['original_sales_order_id']) ? (int) $data['original_sales_order_id'] : null,
            originalInvoiceId: isset($data['original_invoice_id']) ? (int) $data['original_invoice_id'] : null,
            returnNumber: isset($data['return_number']) ? (string) $data['return_number'] : null,
            status: isset($data['status']) ? (string) $data['status'] : 'draft',
            returnDate: isset($data['return_date']) ? (string) $data['return_date'] : null,
            returnReason: isset($data['return_reason']) ? (string) $data['return_reason'] : null,
            exchangeRate: isset($data['exchange_rate']) ? (string) $data['exchange_rate'] : '1.000000',
            subtotal: isset($data['subtotal']) ? (string) $data['subtotal'] : '0.000000',
            taxTotal: isset($data['tax_total']) ? (string) $data['tax_total'] : '0.000000',
            restockingFeeTotal: isset($data['restocking_fee_total']) ? (string) $data['restocking_fee_total'] : '0.000000',
            grandTotal: isset($data['grand_total']) ? (string) $data['grand_total'] : '0.000000',
            creditMemoNumber: isset($data['credit_memo_number']) ? (string) $data['credit_memo_number'] : null,
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
            'original_sales_order_id' => $this->originalSalesOrderId,
            'original_invoice_id' => $this->originalInvoiceId,
            'return_number' => $this->returnNumber,
            'status' => $this->status,
            'return_date' => $this->returnDate,
            'return_reason' => $this->returnReason,
            'currency_id' => $this->currencyId,
            'exchange_rate' => $this->exchangeRate,
            'subtotal' => $this->subtotal,
            'tax_total' => $this->taxTotal,
            'restocking_fee_total' => $this->restockingFeeTotal,
            'grand_total' => $this->grandTotal,
            'credit_memo_number' => $this->creditMemoNumber,
            'journal_entry_id' => $this->journalEntryId,
            'notes' => $this->notes,
            'metadata' => $this->metadata,
        ];
    }
}
