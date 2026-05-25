<?php

declare(strict_types=1);

namespace Modules\HR\Application\Contracts\UseCases\LeaveApplications;

use Modules\Core\Application\Results\Result;

interface CreateLeaveApplicationServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(array $payload): Result;
}