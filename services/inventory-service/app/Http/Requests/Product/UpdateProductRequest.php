<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId  = $this->attributes->get('tenant_id') ?? $this->header('X-Tenant-ID');
        $productId = $this->route('product') ?? $this->route('id');

        return [
            'name'          => ['sometimes', 'string', 'max:255'],
            'sku'           => [
                'sometimes',
                'string',
                'max:100',
                'regex:/^[A-Z0-9\-_\.]+$/i',
                Rule::unique('products')->where(function ($query) use ($tenantId) {
                    return $query->where('tenant_id', $tenantId)
                                 ->whereNull('deleted_at');
                })->ignore($productId),
            ],
            'description'   => ['nullable', 'string', 'max:5000'],
            'category_id'   => ['nullable', 'uuid', 'exists:categories,id'],
            'unit_price'    => ['sometimes', 'numeric', 'min:0', 'max:9999999.99'],
            'cost_price'    => ['nullable', 'numeric', 'min:0', 'max:9999999.99'],
            'unit_of_measure' => ['sometimes', 'string', 'max:50'],
            'images'        => ['nullable', 'array'],
            'images.*'      => ['url', 'max:2048'],
            'attributes'    => ['nullable', 'array'],
            'minimum_stock' => ['nullable', 'integer', 'min:0'],
            'reorder_point' => ['nullable', 'integer', 'min:0'],
            'reorder_quantity' => ['nullable', 'integer', 'min:1'],
            'is_active'     => ['sometimes', 'boolean'],
            'weight'        => ['nullable', 'numeric', 'min:0'],
            'dimensions'    => ['nullable', 'array'],
            'dimensions.length' => ['nullable', 'numeric', 'min:0'],
            'dimensions.width'  => ['nullable', 'numeric', 'min:0'],
            'dimensions.height' => ['nullable', 'numeric', 'min:0'],
            'barcode'       => ['nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'sku.unique' => 'This SKU already exists for your tenant.',
            'sku.regex'  => 'SKU may only contain letters, numbers, hyphens, underscores, and dots.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('sku')) {
            $this->merge(['sku' => strtoupper($this->input('sku'))]);
        }
    }
}
