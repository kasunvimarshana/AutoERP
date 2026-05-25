<?php

declare(strict_types=1);

namespace Modules\HR\Application\Contracts\UseCases\EmployeeSalaryAssignments;

use Modules\Core\Application\Results\Result;

interface CreateEmployeeSalaryAssignmentServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(array $payload): Result;
}