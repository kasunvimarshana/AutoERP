<?php

declare(strict_types=1);

namespace Modules\HR\Application\Contracts\UseCases\LeaveAllocations;

use Modules\Core\Application\Results\Result;

interface CreateLeaveAllocationServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(array $payload): Result;
}