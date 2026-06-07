<?php

declare(strict_types=1);

namespace Modules\Hr\DTOs;

use Modules\Hr\Enums\Gender;

final readonly class UpdateEmployeeData
{
    public function __construct(
        public array $provided,
        public ?int $organizationUnitId = null,
        public ?string $code = null,
        public ?string $firstName = null,
        public ?string $middleName = null,
        public ?string $lastName = null,
        public ?string $displayName = null,
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
        public ?string $defaultHourlyRate = null,
        public ?string $defaultDailyRate = null,
        public ?string $defaultServiceRate = null,
        public ?string $notes = null,
        public ?array $metadata = null,
    ) {}
}
