<?php
declare(strict_types=1);
namespace Modules\HR\Domain\Events;

class EmployeeTerminated
{
    public function __construct(public readonly int $employeeId, public readonly int $tenantId, public readonly string $terminationDate) {}
}
