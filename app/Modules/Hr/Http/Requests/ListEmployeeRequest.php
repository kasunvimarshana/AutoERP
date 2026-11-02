<?php
declare(strict_types=1);
namespace Modules\Hr\Http\Requests;
use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Hr\Enums\EmployeeAvailabilityStatus;
use Modules\Hr\Enums\EmployeeStatus;
final class ListEmployeeRequest extends TenantScopedRequest
{
    public function rules(): array { return ['tenant_id' => ['required', 'integer', 'min:1'], 'organization_unit_id' => ['nullable', 'integer', 'min:1'], 'search' => ['nullable', 'string', 'max:255'], 'status' => ['nullable', Rule::enum(EmployeeStatus::class)], 'availability_status' => ['nullable', Rule::enum(EmployeeAvailabilityStatus::class)], 'department_id' => ['nullable', 'integer', 'min:1'], 'designation_id' => ['nullable', 'integer', 'min:1'], 'employment_type_id' => ['nullable', 'integer', 'min:1'], 'skill_id' => ['nullable', 'integer', 'min:1'], 'certification_id' => ['nullable', 'integer', 'min:1'], 'license_id' => ['nullable', 'integer', 'min:1'], 'at' => ['nullable', 'date'], 'page' => ['nullable', 'integer', 'min:1'], 'per_page' => ['nullable', 'integer', 'between:1,100']]; }
}
