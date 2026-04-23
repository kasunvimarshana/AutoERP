<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListAttributeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => 'nullable|integer|exists:tenants,id',
            'group_id' => 'nullable|integer|exists:attribute_groups,id',
            'name' => 'nullable|string|max:255',
            'type' => 'nullable|string|max:50',
            'is_active' => 'nullable|boolean',
            'is_filterable' => 'nullable|boolean',
            'sort' => 'nullable|string|max:60',
            'per_page' => 'nullable|integer|min:1|max:200',
            'page' => 'nullable|integer|min:1',
        ];
    }
}
