<?php

declare(strict_types=1);

namespace Modules\UOM\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;

final class ConvertUomRequest extends TenantScopedRequest
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
            'quantity' => ['required', 'numeric', 'gt:0'],
            'from_uom_id' => ['required', 'integer', 'min:1', $this->tenantExists('unit_of_measures', 'id')],
            'to_uom_id' => ['required', 'integer', 'min:1', $this->tenantExists('unit_of_measures', 'id'), 'different:from_uom_id'],
        ];
    }
}
