<?php

declare(strict_types=1);

namespace Modules\Supplier\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpsertSupplierVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $required = $this->isMethod('post') ? ['required'] : ['sometimes'];

        return [
            'tenant_id' => ['nullable', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'metadata' => ['nullable', 'array'],
            'source_context' => ['nullable', 'array'],
            'supplier_id' => array_merge($required, ['integer', 'min:1', 'exists:suppliers,id']),
            'vehicle_id' => array_merge($required, ['integer', 'min:1', 'exists:vehicles,id']),
            'relationship_type' => ['nullable', 'string', 'max:80'],
            'vehicle_ownership_id' => ['nullable', 'integer', 'min:1', 'exists:vehicle_ownerships,id'],
            'is_current' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
