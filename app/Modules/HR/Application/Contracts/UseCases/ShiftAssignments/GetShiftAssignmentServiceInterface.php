<?php

declare(strict_types=1);

namespace Modules\HR\Application\Contracts\UseCases\ShiftAssignments;

use Modules\Core\Application\Results\Result;

interface GetShiftAssignmentServiceInterface
{
    public function execute(int|string $id): Result;
}