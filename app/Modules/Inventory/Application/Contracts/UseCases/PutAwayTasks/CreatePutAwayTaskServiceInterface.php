<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Contracts\UseCases\PutAwayTasks;

use Modules\Core\Application\Results\Result;

interface CreatePutAwayTaskServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(array $payload): Result;
}