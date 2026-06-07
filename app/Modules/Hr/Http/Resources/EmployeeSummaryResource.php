<?php

declare(strict_types=1);

namespace Modules\Hr\Http\Resources;

use BackedEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class EmployeeSummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->getKey(), 'tenant_id' => $this->tenant_id, 'organization_unit_id' => $this->organization_unit_id,
            'employee_number' => $this->employee_number, 'code' => $this->code, 'name' => $this->display_name, 'display_name' => $this->display_name,
            'first_name' => $this->first_name, 'middle_name' => $this->middle_name, 'last_name' => $this->last_name,
            'email' => $this->email, 'phone' => $this->phone, 'mobile' => $this->mobile,
            'status' => $this->value($this->status), 'availability_status' => $this->value($this->availability_status),
            'department' => $this->whenLoaded('department', fn () => $this->department ? new HrDepartmentResource($this->department) : null),
            'designation' => $this->whenLoaded('designation', fn () => $this->designation ? new HrDesignationResource($this->designation) : null),
            'employment_type' => $this->whenLoaded('employmentType', fn () => $this->employmentType ? new HrEmploymentTypeResource($this->employmentType) : null),
        ];
    }

    private function value(mixed $value): mixed
    {
        return $value instanceof BackedEnum ? $value->value : $value;
    }
}
