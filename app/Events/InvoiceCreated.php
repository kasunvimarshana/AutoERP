<?php

namespace App\Events;

use App\Contracts\Events\DomainEventInterface;
use App\Models\Invoice;
use DateTimeImmutable;

final class InvoiceCreated implements DomainEventInterface
{
    private DateTimeImmutable $occurredAt;

    public function __construct(
        public readonly Invoice $invoice
    ) {
        $this->occurredAt = new DateTimeImmutable;
    }

    public function getAggregateId(): string
    {
        return $this->invoice->id;
    }

    public function getAggregateType(): string
    {
        return Invoice::class;
    }

    public function getOccurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function toArray(): array
    {
        return [
            'invoice_id' => $this->invoice->id,
            'invoice_number' => $this->invoice->invoice_number,
            'tenant_id' => $this->invoice->tenant_id,
            'status' => $this->invoice->status->value,
            'total' => $this->invoice->total,
            'occurred_at' => $this->occurredAt->format(\DateTimeInterface::ATOM),
        ];
    }
}
