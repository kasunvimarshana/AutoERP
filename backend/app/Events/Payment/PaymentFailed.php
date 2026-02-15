<?php

declare(strict_types=1);

namespace App\Events\Payment;

use App\Core\Events\BaseEvent;

/**
 * Payment Failed Event
 * 
 * Dispatched when a payment attempt fails
 */
class PaymentFailed extends BaseEvent
{
    public function __construct(
        public readonly int $paymentId,
        public readonly string $paymentReference,
        public readonly int $customerId,
        public readonly string $customerName,
        public readonly float $amount,
        public readonly string $paymentMethod,
        public readonly string $failureReason,
        public readonly ?int $invoiceId = null,
        public readonly ?string $invoiceNumber = null,
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
            'failure_reason' => $this->failureReason,
            'invoice_id' => $this->invoiceId,
            'invoice_number' => $this->invoiceNumber,
        ]);
    }
}
