<?php

declare(strict_types=1);

namespace Modules\Warehouse\Presentation\Http\Requests;

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
        return [
            'organization_unit_id' => ['nullable', 'integer', 'exists:organization_units,id'],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('warehouses', 'name')->where('tenant_id', $this->route('tenant')),
            ],
            'code' => ['nullable', 'string', 'max:255'],
            'image_path' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'in:standard,virtual,transit,quarantine'],
            'is_active' => ['nullable', 'boolean'],
            'is_default' => ['nullable', 'boolean'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
