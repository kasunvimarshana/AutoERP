<?php

declare(strict_types=1);

namespace Modules\HR\Application\Contracts\UseCases\LeaveTypes;

use Modules\Core\Application\Results\Result;

interface CreateLeaveTypeServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(array $payload): Result;
}