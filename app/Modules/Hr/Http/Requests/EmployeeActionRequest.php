<?php

declare(strict_types=1);

namespace Modules\Hr\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;

final class EmployeeActionRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'row_version' => ['required', 'integer', 'min:1'],
        ];
    }

    public function rowVersion(): int
    {
        return (int) $this->input('row_version');
    }
}
