<?php

namespace Modules\POS\Domain\Events;

use DateTimeImmutable;

class LoyaltyPointsRedeemed
{
    public readonly DateTimeImmutable $occurredAt;

    public function __construct(
        public readonly string $cardId,
        public readonly string $tenantId,
        public readonly string $customerId,
        public readonly string $pointsRedeemed,
        public readonly string $discountAmount,
        public readonly string $newBalance,
    ) {
        $this->occurredAt = new DateTimeImmutable();
    }
}
