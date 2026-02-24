<?php

namespace Modules\POS\Domain\Events;

use DateTimeImmutable;

class LoyaltyPointsAccrued
{
    public readonly DateTimeImmutable $occurredAt;

    public function __construct(
        public readonly string $cardId,
        public readonly string $tenantId,
        public readonly string $customerId,
        public readonly string $pointsAdded,
        public readonly string $newBalance,
    ) {
        $this->occurredAt = new DateTimeImmutable();
    }
}
