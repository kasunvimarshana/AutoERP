<?php

declare(strict_types=1);

namespace App\Events\Payment;

use App\Core\Events\BaseEvent;

/**
 * Payment Received Event
 * 
 * Dispatched when a payment is successfully received
 */
class PaymentReceived extends BaseEvent
{
    public function __construct(
        public readonly int $paymentId,
        public readonly string $paymentReference,
        public readonly int $customerId,
        public readonly string $customerName,
        public readonly float $amount,
        public readonly string $paymentMethod, // cash, card, bank_transfer, etc.
        public readonly ?int $invoiceId = null,
        public readonly ?string $invoiceNumber = null,
        public readonly ?\DateTimeInterface $receivedAt = null,
        int $tenantId,
        ?int $userId = null,
        ?array $metadata = null
    ) {
        parent::__construct($tenantId, $userId, $metadata);
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'payment_id' => $this->paymentId,
            'payment_reference' => $this->paymentReference,
            'customer_id' => $this->customerId,
            'customer_name' => $this->customerName,
            'amount' => $this->amount,
            'payment_method' => $this->paymentMethod,
            'invoice_id' => $this->invoiceId,
            'invoice_number' => $this->invoiceNumber,
            'received_at' => $this->receivedAt?->format('Y-m-d H:i:s'),
        ]);
    }
}
