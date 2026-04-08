<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;

class CycleCountCompleted extends BaseEvent
{
    public function __construct(
        int $tenantId,
        public readonly string $cycleCountId,
        public readonly int $totalLines,
        public readonly int $varianceLines,
    ) {
        parent::__construct($tenantId);
    }
}
