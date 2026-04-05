<?php
declare(strict_types=1);
namespace Modules\HR\Domain\Events;

class EmployeeUpdated
{
    public function __construct(public readonly int $employeeId, public readonly int $tenantId) {}
}
