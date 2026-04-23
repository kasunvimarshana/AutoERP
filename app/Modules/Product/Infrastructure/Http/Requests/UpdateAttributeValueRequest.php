<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAttributeValueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'attribute_id' => 'sometimes|integer|exists:attributes,id',
            'value' => 'sometimes|string|max:255',
            'sort_order' => 'nullable|integer',
            'label' => 'nullable|string|max:255',
            'color_code' => 'nullable|string|max:10',
            'is_active' => 'nullable|boolean',
            'metadata' => 'nullable|array',
        ];
    }
}
