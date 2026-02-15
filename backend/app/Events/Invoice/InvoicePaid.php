<?php

declare(strict_types=1);

namespace App\Events\Invoice;

use App\Core\Events\BaseEvent;

/**
 * Invoice Paid Event
 * 
 * Dispatched when an invoice is fully paid
 */
class InvoicePaid extends BaseEvent
{
    public function __construct(
        public readonly int $invoiceId,
        public readonly string $invoiceNumber,
        public readonly int $customerId,
        public readonly string $customerName,
        public readonly float $totalAmount,
        public readonly float $paidAmount,
        public readonly \DateTimeInterface $paidAt,
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
            'paid_amount' => $this->paidAmount,
            'paid_at' => $this->paidAt->format('Y-m-d H:i:s'),
        ]);
    }
}
