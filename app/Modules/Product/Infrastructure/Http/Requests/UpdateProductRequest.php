<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Product\Domain\ValueObjects\ProductType;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'                                 => 'sometimes|required|string|max:255',
            'description'                          => 'nullable|string',
            'price'                                => 'sometimes|required|numeric|min:0',
            'currency'                             => 'nullable|string|size:3',
            'category'                             => 'nullable|string|max:100',
            'status'                               => 'nullable|string|in:active,inactive,draft',
            'type'                                 => 'nullable|string|in:'.implode(',', ProductType::VALID_TYPES),
            'units_of_measure'                     => 'nullable|array',
            'units_of_measure.*.unit'              => 'required_with:units_of_measure|string|max:50',
            'units_of_measure.*.type'              => 'required_with:units_of_measure|string|in:buying,selling,inventory',
            'units_of_measure.*.conversion_factor' => 'nullable|numeric|min:0.0001',
            'attributes'                           => 'nullable|array',
            'metadata'                             => 'nullable|array',
            'product_attributes'                   => 'nullable|array',
            'product_attributes.*.code'            => 'required_with:product_attributes|string|max:50',
            'product_attributes.*.name'            => 'required_with:product_attributes|string|max:100',
            'product_attributes.*.allowed_values'  => 'nullable|array',
            'product_attributes.*.allowed_values.*' => 'string|max:100',
        ];
    }
}
