<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAttributeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'group_id' => 'nullable|integer|exists:attribute_groups,id',
            'name' => 'sometimes|string|max:255',
            'type' => 'sometimes|string|max:50',
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
