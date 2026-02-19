<?php

namespace App\Contracts\Events;

interface DomainEventInterface
{
    public function getAggregateId(): string;

    public function getAggregateType(): string;

    public function getOccurredAt(): \DateTimeImmutable;

    public function toArray(): array;
}
