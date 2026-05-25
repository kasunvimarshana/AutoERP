<?php

declare(strict_types=1);

namespace Modules\HR\Application\Contracts\UseCases\LeaveTypes;

use Modules\Core\Application\Results\Result;

interface DeleteLeaveTypeServiceInterface
{
    public function execute(int|string $id): Result;
}