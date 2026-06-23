<?php

declare(strict_types=1);

namespace Modules\UOM\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;

final class ListUomConversionRequest extends TenantScopedRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'tenant_id' => ['nullable', 'integer', 'min:1', 'exists:tenants,id'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1', $this->tenantExists('organization_units', 'id')],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:'.(int) config('uom.pagination.max_per_page', 200)],
            'from_uom_id' => ['nullable', 'integer', 'min:1', $this->tenantExists('unit_of_measures', 'id')],
            'to_uom_id' => ['nullable', 'integer', 'min:1', $this->tenantExists('unit_of_measures', 'id')],
            'search' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
