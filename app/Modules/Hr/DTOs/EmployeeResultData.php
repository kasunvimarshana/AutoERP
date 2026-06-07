<?php
declare(strict_types=1);
namespace Modules\Hr\DTOs;
use Modules\Hr\Enums\EmployeeAvailabilityStatus;
use Modules\Hr\Enums\EmployeeStatus;
final readonly class EmployeeResultData { public function __construct(public int $id, public int $tenantId, public ?int $organizationUnitId, public string $employeeNumber, public string $displayName, public EmployeeStatus $status, public EmployeeAvailabilityStatus $availabilityStatus) {} }
