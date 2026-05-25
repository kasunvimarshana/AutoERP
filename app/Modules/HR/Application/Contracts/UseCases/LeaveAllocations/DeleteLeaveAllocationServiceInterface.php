<?php

declare(strict_types=1);

namespace Modules\HR\Application\Contracts\UseCases\LeaveAllocations;

use Modules\Core\Application\Results\Result;

interface DeleteLeaveAllocationServiceInterface
{
    public function execute(int|string $id): Result;
}