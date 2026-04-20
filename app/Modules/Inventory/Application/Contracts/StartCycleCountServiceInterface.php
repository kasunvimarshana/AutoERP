<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Contracts;

use Modules\Inventory\Domain\Entities\CycleCountHeader;

interface StartCycleCountServiceInterface
{
    public function execute(int $tenantId, int $countId): CycleCountHeader;
}
