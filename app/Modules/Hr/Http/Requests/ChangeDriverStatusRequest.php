<?php

declare(strict_types=1);

namespace Modules\Hr\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Hr\Enums\EmployeeAvailabilityStatus;
use Modules\Hr\Enums\EmployeeStatus;

final class ChangeDriverStatusRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'status' => ['required', Rule::enum(EmployeeStatus::class)],
            'availability_status' => ['nullable', Rule::enum(EmployeeAvailabilityStatus::class)],
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }
}
