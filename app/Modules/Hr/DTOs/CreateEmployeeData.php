<?php

declare(strict_types=1);

namespace Modules\Hr\DTOs;

use Modules\Hr\Enums\EmployeeAvailabilityStatus;
use Modules\Hr\Enums\EmployeeStatus;
use Modules\Hr\Enums\Gender;

final readonly class CreateEmployeeData
{
    public function __construct(
        public int $tenantId,
        public string $firstName,
        public string $displayName,
        public ?int $organizationUnitId = null,
        public ?string $employeeNumber = null,
        public ?string $code = null,
        public ?string $middleName = null,
        public ?string $lastName = null,
        public ?string $email = null,
        public ?string $phone = null,
        public ?string $mobile = null,
        public ?int $departmentId = null,
        public ?int $designationId = null,
        public ?int $employmentTypeId = null,
        public ?int $reportingManagerId = null,
        public ?string $joinedDate = null,
        public ?string $resignedDate = null,
        public ?string $dateOfBirth = null,
        public ?Gender $gender = null,
        public EmployeeStatus $status = EmployeeStatus::PendingApproval,
        public EmployeeAvailabilityStatus $availabilityStatus = EmployeeAvailabilityStatus::Available,
        public string $defaultHourlyRate = '0.000000',
        public string $defaultDailyRate = '0.000000',
        public string $defaultServiceRate = '0.000000',
        public ?string $notes = null,
        public ?array $metadata = null,
        public ?int $createdBy = null,
        public array $contacts = [],
        public array $addresses = [],
        public array $documents = [],
        public array $skills = [],
        public array $certifications = [],
        public array $licenses = [],
        public array $rates = [],
        public ?EmployeeAvailabilityData $availability = null,
    ) {}
}
