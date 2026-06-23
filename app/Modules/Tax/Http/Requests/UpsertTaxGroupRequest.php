<?php

declare(strict_types=1);

namespace Modules\Tax\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;

final class UpsertTaxGroupRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'code' => ['required', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:255'],
            'is_default' => ['nullable', 'boolean'],
            'active' => ['nullable', 'boolean'],
            'lines' => ['nullable', 'array'],
            'lines.*.tax_id' => ['required', 'integer', 'min:1', $this->tenantExists('taxes', 'id')],
            'lines.*.sequence' => ['required', 'integer', 'min:1'],
            'lines.*.active' => ['nullable', 'boolean'],
        ];
    }
}
