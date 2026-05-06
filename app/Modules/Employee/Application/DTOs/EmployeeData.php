<?php

declare(strict_types=1);

namespace Modules\Employee\Application\DTOs;

class EmployeeData
{
    /**
     * @param  array<string, mixed>|null  $metadata
     * @param  array<string, mixed>|null  $user
     */
    public function __construct(
        public readonly int $tenantId,
        public readonly ?int $userId = null,
        public readonly ?string $employeeCode = null,
        public readonly ?int $orgUnitId = null,
        public readonly ?string $jobTitle = null,
        public readonly ?string $hireDate = null,
        public readonly ?string $terminationDate = null,
        public readonly ?array $metadata = null,
        public readonly ?array $user = null,
        public readonly ?int $id = null,
        public readonly int $rowVersion = 1,
    )
    {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            tenantId: (int) $data['tenant_id'],
            userId: isset($data['user_id']) ? (int) $data['user_id'] : null,
            employeeCode: isset($data['employee_code']) ? (string) $data['employee_code'] : null,
            orgUnitId: isset($data['org_unit_id']) ? (int) $data['org_unit_id'] : null,
            jobTitle: isset($data['job_title']) ? (string) $data['job_title'] : null,
            hireDate: isset($data['hire_date']) ? (string) $data['hire_date'] : null,
            terminationDate: isset($data['termination_date']) ? (string) $data['termination_date'] : null,
            metadata: isset($data['metadata']) && is_array($data['metadata']) ? $data['metadata'] : null,
            user: isset($data['user']) && is_array($data['user']) ? $data['user'] : null,
            id: isset($data['id']) ? (int) $data['id'] : null,
            rowVersion: isset($data['row_version']) ? (int) $data['row_version'] : 1,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenantId,
            'user_id' => $this->userId,
            'employee_code' => $this->employeeCode,
            'org_unit_id' => $this->orgUnitId,
            'job_title' => $this->jobTitle,
            'hire_date' => $this->hireDate,
            'termination_date' => $this->terminationDate,
            'metadata' => $this->metadata,
            'user' => $this->user,
        ];
    }
}
