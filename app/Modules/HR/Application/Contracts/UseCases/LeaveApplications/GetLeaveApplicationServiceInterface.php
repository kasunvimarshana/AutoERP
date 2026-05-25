<?php

declare(strict_types=1);

namespace Modules\HR\Application\Contracts\UseCases\LeaveApplications;

use Modules\Core\Application\Results\Result;

interface GetLeaveApplicationServiceInterface
{
    public function execute(int|string $id): Result;
}