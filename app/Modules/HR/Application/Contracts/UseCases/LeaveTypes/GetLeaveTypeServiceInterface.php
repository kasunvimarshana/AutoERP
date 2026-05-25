<?php

declare(strict_types=1);

namespace Modules\HR\Application\Contracts\UseCases\LeaveTypes;

use Modules\Core\Application\Results\Result;

interface GetLeaveTypeServiceInterface
{
    public function execute(int|string $id): Result;
}