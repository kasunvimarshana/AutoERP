<?php

declare(strict_types=1);

namespace Modules\Product\Interfaces\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Product\Domain\Enums\ProductAttributeType;

class SetProductAttributesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $attributeTypes = implode(',', array_column(ProductAttributeType::cases(), 'value'));

        return [
            'attributes' => ['required', 'array', 'min:1'],
            'attributes.*.attribute_key' => ['required', 'string', 'max:100'],
            'attributes.*.attribute_label' => ['required', 'string', 'max:255'],
            'attributes.*.attribute_value' => ['required', 'string'],
            'attributes.*.attribute_type' => ['nullable', 'string', "in:{$attributeTypes}"],
            'attributes.*.sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
