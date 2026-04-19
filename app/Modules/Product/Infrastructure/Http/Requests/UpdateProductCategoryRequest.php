<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = $this->input('tenant_id');
        $productCategoryId = (int) $this->route('product_category');

        return [
            'tenant_id' => 'required|integer|exists:tenants,id',
            'parent_id' => 'nullable|integer|exists:product_categories,id',
            'name' => 'required|string|max:255',
            'image_path' => 'nullable|string|max:255',
            'slug' => 'required|string|max:255',
            'code' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('product_categories', 'code')
                    ->where(fn ($query) => $query->where('tenant_id', $tenantId))
                    ->ignore($productCategoryId),
            ],
            'path' => 'nullable|string|max:255',
            'depth' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
            'description' => 'nullable|string',
            'attributes' => 'nullable|array',
            'metadata' => 'nullable|array',
        ];
    }
}
