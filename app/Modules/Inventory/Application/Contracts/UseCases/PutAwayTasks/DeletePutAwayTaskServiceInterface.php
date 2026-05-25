<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Contracts\UseCases\PutAwayTasks;

use Modules\Core\Application\Results\Result;

interface DeletePutAwayTaskServiceInterface
{
    public function execute(int|string $id): Result;
}