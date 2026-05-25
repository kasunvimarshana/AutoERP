<?php

declare(strict_types=1);

namespace Modules\Core\Domain\Events;

use DateTimeImmutable;

abstract class AbstractDomainEvent implements DomainEventInterface
{
    /**
     * @param array<string, scalar|array|null> $payload
     */
    final public function __construct(
        private readonly DateTimeImmutable $occurredAt,
        private readonly array $payload,
    ) {
    }

    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }

    /**
     * @return array<string, scalar|array|null>
     */
    public function payload(): array
    {
        return $this->payload;
    }
}
