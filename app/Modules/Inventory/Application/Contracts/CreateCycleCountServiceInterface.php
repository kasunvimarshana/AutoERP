<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Contracts;

use Modules\Inventory\Domain\Entities\CycleCountHeader;

interface CreateCycleCountServiceInterface
{
    public function execute(array $data): CycleCountHeader;
}
