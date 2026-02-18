<?php

namespace App\Modules\POS\DTOs;

class PaymentDTO
{
    public function __construct(
        public readonly int $invoiceId,
        public readonly int $customerId,
        public readonly string $paymentMethod,
        public readonly float $amount,
        public readonly string $paymentDate,
        public readonly ?string $referenceNumber = null,
        public readonly ?string $notes = null,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            invoiceId: $data['invoice_id'],
            customerId: $data['customer_id'],
            paymentMethod: $data['payment_method'],
            amount: $data['amount'],
            paymentDate: $data['payment_date'],
            referenceNumber: $data['reference_number'] ?? null,
            notes: $data['notes'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'invoice_id' => $this->invoiceId,
            'customer_id' => $this->customerId,
            'payment_method' => $this->paymentMethod,
            'amount' => $this->amount,
            'payment_date' => $this->paymentDate,
            'reference_number' => $this->referenceNumber,
            'notes' => $this->notes,
        ];
    }
}
