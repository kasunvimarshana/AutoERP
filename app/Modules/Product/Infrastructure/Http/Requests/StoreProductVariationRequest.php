<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductVariationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sku'              => 'required|string|max:100',
            'name'             => 'required|string|max:255',
            'price'            => 'required|numeric|min:0',
            'currency'         => 'nullable|string|size:3',
            'attribute_values' => 'nullable|array',
            'status'           => 'nullable|string|in:active,inactive',
            'sort_order'       => 'nullable|integer|min:0',
            'metadata'         => 'nullable|array',
        ];
    }
}
