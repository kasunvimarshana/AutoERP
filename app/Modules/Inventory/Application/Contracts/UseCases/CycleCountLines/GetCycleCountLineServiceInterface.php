<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Contracts\UseCases\CycleCountLines;

use Modules\Core\Application\Results\Result;

interface GetCycleCountLineServiceInterface
{
    public function execute(int|string $id): Result;
}