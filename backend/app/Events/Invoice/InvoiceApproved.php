<?php

declare(strict_types=1);

namespace App\Events\Invoice;

use App\Core\Events\BaseEvent;

/**
 * Invoice Approved Event
 * 
 * Dispatched when an invoice is approved
 */
class InvoiceApproved extends BaseEvent
{
    public function __construct(
        public readonly int $invoiceId,
        public readonly string $invoiceNumber,
        public readonly int $customerId,
        public readonly string $customerName,
        public readonly float $totalAmount,
        public readonly ?\DateTimeInterface $dueDate = null,
        int $tenantId,
        ?int $userId = null,
        ?array $metadata = null
    ) {
        parent::__construct($tenantId, $userId, $metadata);
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'invoice_id' => $this->invoiceId,
            'invoice_number' => $this->invoiceNumber,
            'customer_id' => $this->customerId,
            'customer_name' => $this->customerName,
            'total_amount' => $this->totalAmount,
            'due_date' => $this->dueDate?->format('Y-m-d'),
        ]);
    }
}
