<?php

declare(strict_types=1);

namespace Modules\Hr\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Hr\Enums\EmployeeAvailabilityStatus;
use Modules\Hr\Enums\EmployeeStatus;

final class ListDriverRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::enum(EmployeeStatus::class)],
            'availability_status' => ['nullable', Rule::enum(EmployeeAvailabilityStatus::class)],
            'license_status' => ['nullable', Rule::in(['valid', 'expiring_soon', 'expired'])],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ];
    }

    public function perPage(): int
    {
        return (int) $this->input('per_page', 25);
    }
}
