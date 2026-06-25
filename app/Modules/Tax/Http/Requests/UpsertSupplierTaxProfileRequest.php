<?php

declare(strict_types=1);

namespace Modules\Tax\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;

final class UpsertSupplierTaxProfileRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'supplier_id' => ['required', 'integer', 'min:1', $this->tenantExists('suppliers', 'id')],
            'tax_group_id' => ['nullable', 'integer', 'min:1', $this->tenantExists('tax_groups', 'id')],
            'registration_number' => ['nullable', 'string', 'max:255'],
            'exemption_status' => ['required', Rule::in(config('tax.exemption_statuses', []))],
            'active' => ['nullable', 'boolean'],
        ];
    }
}
