<?php

declare(strict_types=1);

namespace Modules\Warehouse\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWarehouseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = (int) $this->input('tenant_id');

        return [
            'tenant_id' => 'required|integer|exists:tenants,id',
            'org_unit_id' => [
                'nullable',
                'integer',
                Rule::exists('org_units', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'name' => 'required|string|max:255',
            'code' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('warehouses', 'code')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'image_path' => 'nullable|string|max:500',
            'type' => 'nullable|in:standard,virtual,transit,quarantine',
            'address_id' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
            'is_default' => 'nullable|boolean',
            'metadata' => 'nullable|array',
        ];
    }
}
