<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAttributeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => 'required|integer|exists:tenants,id',
            'group_id' => 'nullable|integer|exists:attribute_groups,id',
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:50',
            'is_required' => 'nullable|boolean',
            'code' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'sort_order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
            'is_filterable' => 'nullable|boolean',
            'metadata' => 'nullable|array',
        ];
    }
}
