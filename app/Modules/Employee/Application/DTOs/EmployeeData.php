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
        public readonly int $tenant_id,
        public readonly ?int $user_id = null,
        public readonly ?string $employee_code = null,
        public readonly ?int $org_unit_id = null,
        public readonly ?string $job_title = null,
        public readonly ?string $hire_date = null,
        public readonly ?string $termination_date = null,
        public readonly ?array $metadata = null,
        public readonly ?array $user = null,
        public readonly ?int $id = null,
        public readonly int $row_version = 1,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            tenant_id: (int) $data['tenant_id'],
            user_id: isset($data['user_id']) ? (int) $data['user_id'] : null,
            employee_code: isset($data['employee_code']) ? (string) $data['employee_code'] : null,
            org_unit_id: isset($data['org_unit_id']) ? (int) $data['org_unit_id'] : null,
            job_title: isset($data['job_title']) ? (string) $data['job_title'] : null,
            hire_date: isset($data['hire_date']) ? (string) $data['hire_date'] : null,
            termination_date: isset($data['termination_date']) ? (string) $data['termination_date'] : null,
            metadata: isset($data['metadata']) && is_array($data['metadata']) ? $data['metadata'] : null,
            user: isset($data['user']) && is_array($data['user']) ? $data['user'] : null,
            id: isset($data['id']) ? (int) $data['id'] : null,
            row_version: isset($data['row_version']) ? (int) $data['row_version'] : 1,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'user_id' => $this->user_id,
            'employee_code' => $this->employee_code,
            'org_unit_id' => $this->org_unit_id,
            'job_title' => $this->job_title,
            'hire_date' => $this->hire_date,
            'termination_date' => $this->termination_date,
            'metadata' => $this->metadata,
            'user' => $this->user,
        ];
    }
}
