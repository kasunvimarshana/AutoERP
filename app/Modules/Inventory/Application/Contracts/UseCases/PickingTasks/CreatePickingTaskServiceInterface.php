<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Contracts\UseCases\PickingTasks;

use Modules\Core\Application\Results\Result;

interface CreatePickingTaskServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(array $payload): Result;
}