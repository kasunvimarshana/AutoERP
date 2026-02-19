<?php

namespace App\Events;

use App\Contracts\Events\DomainEventInterface;
use App\Models\Payment;
use DateTimeImmutable;

final class PaymentRecorded implements DomainEventInterface
{
    private DateTimeImmutable $occurredAt;

    public function __construct(
        public readonly Payment $payment
    ) {
        $this->occurredAt = new DateTimeImmutable;
    }

    public function getAggregateId(): string
    {
        return $this->payment->id;
    }

    public function getAggregateType(): string
    {
        return Payment::class;
    }

    public function getOccurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function toArray(): array
    {
        return [
            'payment_id' => $this->payment->id,
            'payment_number' => $this->payment->payment_number,
            'tenant_id' => $this->payment->tenant_id,
            'amount' => $this->payment->amount,
            'invoice_id' => $this->payment->invoice_id,
            'occurred_at' => $this->occurredAt->format(\DateTimeInterface::ATOM),
        ];
    }
}
