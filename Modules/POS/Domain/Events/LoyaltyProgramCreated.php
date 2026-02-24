<?php

namespace Modules\POS\Domain\Events;

use DateTimeImmutable;

class LoyaltyProgramCreated
{
    public readonly DateTimeImmutable $occurredAt;

    public function __construct(
        public readonly string $programId,
        public readonly string $tenantId,
        public readonly string $name,
    ) {
        $this->occurredAt = new DateTimeImmutable();
    }
}
