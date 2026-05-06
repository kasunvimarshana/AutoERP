<?php

declare(strict_types=1);

namespace Modules\Employee\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->getId(),
            'tenant_id' => $this->getTenantId(),
            'user_id' => $this->getUserId(),
            'employee_code' => $this->getEmployeeCode(),
            'org_unit_id' => $this->getOrgUnitId(),
            'job_title' => $this->getJobTitle(),
            'hire_date' => $this->getHireDate()?->format('Y-m-d'),
            'termination_date' => $this->getTerminationDate()?->format('Y-m-d'),
            'metadata' => $this->getMetadata(),
            'row_version' => $this->getRowVersion(),
            'created_at' => $this->getCreatedAt()->format('c'),
            'updated_at' => $this->getUpdatedAt()->format('c'),
        ];
    }
}
