<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;

class ExpiredStockReservationsReleased extends BaseEvent
{
    public function __construct(
        int $tenantId,
        public readonly int $releasedCount,
        public readonly ?string $expiresBefore = null,
    ) {
        parent::__construct($tenantId);
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'releasedCount' => $this->releasedCount,
            'expiresBefore' => $this->expiresBefore,
        ]);
    }
}
