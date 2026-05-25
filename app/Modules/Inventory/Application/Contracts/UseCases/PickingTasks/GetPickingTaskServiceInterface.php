<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Contracts\UseCases\PickingTasks;

use Modules\Core\Application\Results\Result;

interface GetPickingTaskServiceInterface
{
    public function execute(int|string $id): Result;
}