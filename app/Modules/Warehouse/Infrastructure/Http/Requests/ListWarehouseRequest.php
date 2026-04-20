<?php

declare(strict_types=1);

namespace Modules\Warehouse\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListWarehouseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => 'required|integer|exists:tenants,id',
            'org_unit_id' => 'nullable|integer|exists:org_units,id',
            'name' => 'nullable|string|max:255',
            'code' => 'nullable|string|max:100',
            'type' => 'nullable|in:standard,virtual,transit,quarantine',
            'is_active' => 'nullable|boolean',
            'is_default' => 'nullable|boolean',
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
            'sort' => 'nullable|string|max:64',
        ];
    }
}
