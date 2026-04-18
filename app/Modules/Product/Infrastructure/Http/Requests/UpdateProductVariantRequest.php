<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductVariantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $productId = $this->input('product_id');
        $productVariantId = (int) $this->route('product_variant');

        return [
            'product_id' => 'required|integer|exists:products,id',
            'name' => 'required|string|max:255',
            'sku' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('product_variants', 'sku')
                    ->where(fn ($query) => $query->where('product_id', $productId))
                    ->ignore($productVariantId),
            ],
            'is_default' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'metadata' => 'nullable|array',
        ];
    }
}
