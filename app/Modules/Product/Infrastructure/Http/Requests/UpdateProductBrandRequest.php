<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductBrandRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = $this->input('tenant_id');
        $productBrandId = (int) $this->route('product_brand');

        return [
            'tenant_id' => 'required|integer|exists:tenants,id',
            'parent_id' => 'nullable|integer|exists:product_brands,id',
            'name' => 'required|string|max:255',
            'image_path' => 'nullable|string|max:255',
            'slug' => 'required|string|max:255',
            'code' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('product_brands', 'code')
                    ->where(fn ($query) => $query->where('tenant_id', $tenantId))
                    ->ignore($productBrandId),
            ],
            'path' => 'nullable|string|max:255',
            'depth' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
            'website' => 'nullable|url|max:255',
            'description' => 'nullable|string',
            'attributes' => 'nullable|array',
            'metadata' => 'nullable|array',
        ];
    }
}
