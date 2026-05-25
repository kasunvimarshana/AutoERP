<?php

declare(strict_types=1);

namespace Modules\HR\Application\Contracts\UseCases\LeaveApplications;

use Modules\Core\Application\Results\Result;

interface DeleteLeaveApplicationServiceInterface
{
    public function execute(int|string $id): Result;
}