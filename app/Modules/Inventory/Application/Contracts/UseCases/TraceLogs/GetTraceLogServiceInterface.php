<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Contracts\UseCases\TraceLogs;

use Modules\Core\Application\Results\Result;

interface GetTraceLogServiceInterface
{
    public function execute(int|string $id): Result;
}