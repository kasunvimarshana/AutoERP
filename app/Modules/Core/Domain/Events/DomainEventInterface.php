<?php

declare(strict_types=1);

namespace Modules\Core\Domain\Events;

use DateTimeImmutable;

interface DomainEventInterface
{
    public function eventName(): string;

    public function occurredAt(): DateTimeImmutable;

    /**
     * @return array<string, scalar|array|null>
     */
    public function payload(): array;
}
