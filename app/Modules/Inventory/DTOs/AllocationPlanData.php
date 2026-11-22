<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

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
