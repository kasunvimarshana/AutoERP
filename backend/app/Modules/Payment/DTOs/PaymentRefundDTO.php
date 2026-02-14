<?php

namespace App\Modules\Payment\DTOs;

class PaymentRefundDTO
{
    public function __construct(
        public ?string $id = null,
        public ?string $payment_id = null,
        public ?string $refund_number = null,
        public ?string $refund_date = null,
        public ?float $amount = 0.00,
        public ?string $reason = null,
        public ?string $status = 'pending',
        public ?string $processed_by = null,
        public ?string $notes = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? null,
            payment_id: $data['payment_id'] ?? null,
            refund_number: $data['refund_number'] ?? null,
            refund_date: $data['refund_date'] ?? null,
            amount: isset($data['amount']) ? (float)$data['amount'] : 0.00,
            reason: $data['reason'] ?? null,
            status: $data['status'] ?? 'pending',
            processed_by: $data['processed_by'] ?? null,
            notes: $data['notes'] ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'id' => $this->id,
            'payment_id' => $this->payment_id,
            'refund_number' => $this->refund_number,
            'refund_date' => $this->refund_date,
            'amount' => $this->amount,
            'reason' => $this->reason,
            'status' => $this->status,
            'processed_by' => $this->processed_by,
            'notes' => $this->notes,
        ], fn($value) => !is_null($value));
    }
}
