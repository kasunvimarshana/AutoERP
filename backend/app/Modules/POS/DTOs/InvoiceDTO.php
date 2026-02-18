<?php

namespace App\Modules\POS\DTOs;

class InvoiceDTO
{
    public function __construct(
        public readonly int $customerId,
        public readonly ?int $salesOrderId,
        public readonly string $invoiceDate,
        public readonly string $dueDate,
        public readonly array $items,
        public readonly ?string $notes = null,
        public readonly ?string $termsAndConditions = null,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            customerId: $data['customer_id'],
            salesOrderId: $data['sales_order_id'] ?? null,
            invoiceDate: $data['invoice_date'],
            dueDate: $data['due_date'],
            items: $data['items'] ?? [],
            notes: $data['notes'] ?? null,
            termsAndConditions: $data['terms_and_conditions'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'customer_id' => $this->customerId,
            'sales_order_id' => $this->salesOrderId,
            'invoice_date' => $this->invoiceDate,
            'due_date' => $this->dueDate,
            'notes' => $this->notes,
            'terms_and_conditions' => $this->termsAndConditions,
        ];
    }
}
