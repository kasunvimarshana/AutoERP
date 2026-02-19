<?php

declare(strict_types=1);

namespace Modules\Product\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Product\Enums\ProductType;

/**
 * Store Product Request
 */
class StoreProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', \Modules\Product\Models\Product::class);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('products', 'code')
                    ->where('tenant_id', $this->user()->currentTenant()->id)
                    ->whereNull('deleted_at'),
            ],
            'type' => ['required', Rule::enum(ProductType::class)],
            'category_id' => [
                'nullable',
                Rule::exists('product_categories', 'id')
                    ->where('tenant_id', $this->user()->currentTenant()->id)
                    ->whereNull('deleted_at'),
            ],
            'buying_unit_id' => [
                'nullable',
                Rule::exists('units', 'id')
                    ->where('tenant_id', $this->user()->currentTenant()->id),
            ],
            'selling_unit_id' => [
                'nullable',
                Rule::exists('units', 'id')
                    ->where('tenant_id', $this->user()->currentTenant()->id),
            ],
            'description' => ['nullable', 'string', 'max:5000'],
            'metadata' => ['nullable', 'array'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => 'product name',
            'code' => 'product code',
            'type' => 'product type',
            'category_id' => 'category',
            'buying_unit_id' => 'buying unit',
            'selling_unit_id' => 'selling unit',
        ];
    }
}
