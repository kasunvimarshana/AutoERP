<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Events;

use Modules\Inventory\Domain\Entities\CycleCount;

class CycleCountCreated
{
    public function __construct(
        public readonly CycleCount $cycleCount,
    ) {}
}
