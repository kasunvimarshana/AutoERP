<?php

declare(strict_types=1);

namespace Modules\ServiceCenter\Domain\Events;

class ServiceOrderCompleted
{
    public function __construct(
        public readonly string $tenantId,
        public readonly string $serviceOrderId,
        public readonly string $orderNumber,
        public readonly string $assetId,
        public readonly string $totalCost,
        public readonly \DateTimeImmutable $completedAt,
    ) {
    }
}
