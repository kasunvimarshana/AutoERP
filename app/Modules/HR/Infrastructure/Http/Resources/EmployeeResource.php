<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'              => $this->getId(),
            'tenant_id'       => $this->getTenantId(),
            'first_name'      => $this->getFirstName()->value(),
            'last_name'       => $this->getLastName()->value(),
            'email'           => $this->getEmail()->value(),
            'phone'           => $this->getPhone()?->value(),
            'date_of_birth'   => $this->getDateOfBirth(),
            'gender'          => $this->getGender(),
            'address'         => $this->getAddress(),
            'employee_number' => $this->getEmployeeNumber()->value(),
            'hire_date'       => $this->getHireDate()->format('Y-m-d'),
            'employment_type' => $this->getEmploymentType(),
            'status'          => $this->getStatus(),
            'department_id'   => $this->getDepartmentId(),
            'position_id'     => $this->getPositionId(),
            'manager_id'      => $this->getManagerId(),
            'salary'          => $this->getSalary(),
            'currency'        => $this->getCurrency(),
            'org_unit_id'     => $this->getOrgUnitId(),
            'metadata'        => $this->getMetadata()->toArray(),
            'is_active'       => $this->isActive(),
            'user_id'         => $this->getUserId(),
            'created_at'      => $this->getCreatedAt()->format('c'),
            'updated_at'      => $this->getUpdatedAt()->format('c'),
        ];
    }
}
