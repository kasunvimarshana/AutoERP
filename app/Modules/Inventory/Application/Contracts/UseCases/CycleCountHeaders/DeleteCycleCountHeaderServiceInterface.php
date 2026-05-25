<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Contracts\UseCases\CycleCountHeaders;

use Modules\Core\Application\Results\Result;

interface DeleteCycleCountHeaderServiceInterface
{
    public function execute(int|string $id): Result;
}