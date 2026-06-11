<?php

declare(strict_types=1);

namespace Modules\Inventory\DTOs;

use Modules\Inventory\Enums\AllocationMethod;

final readonly class AllocationPlanData
{
    /**
     * @param  list<AllocationPlanLineData>  $lines
     */
    public function __construct(
        public AllocationMethod $method,
        public string $quantity,
        public array $lines,
    ) {}
}
