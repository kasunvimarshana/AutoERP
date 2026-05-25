<?php

declare(strict_types=1);

namespace Modules\HR\Application\Contracts\UseCases\EmployeeSalaryAssignments;

use Modules\Core\Application\Results\Result;

interface GetEmployeeSalaryAssignmentServiceInterface
{
    public function execute(int|string $id): Result;
}