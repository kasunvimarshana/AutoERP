<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Inventory\Strategies\Allocation;

use Modules\Inventory\Enums\AllocationMethod;

final class FifoAllocationStrategy extends AbstractAllocationStrategy
{
    protected function method(): AllocationMethod
    {
        return AllocationMethod::FIFO;
    }
}
