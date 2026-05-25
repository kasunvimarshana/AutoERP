<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Contracts\UseCases\PickingTasks;

use Modules\Core\Application\Results\Result;

interface ListPickingTasksServiceInterface
{
    /**
     * @param array<string, mixed> $criteria
     */
    public function execute(array $criteria, int $perPage, int $page): Result;
}