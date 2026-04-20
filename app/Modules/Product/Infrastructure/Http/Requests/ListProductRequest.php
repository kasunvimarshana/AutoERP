<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => 'nullable|integer|min:1',
            'category_id' => 'nullable|integer|min:1',
            'brand_id' => 'nullable|integer|min:1',
            'org_unit_id' => 'nullable|integer|min:1',
            'type' => 'nullable|string|in:physical,service,digital,combo,variable',
            'name' => 'nullable|string|max:255',
            'slug' => 'nullable|string|max:255',
            'sku' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
            'sort' => 'nullable|string|max:50',
            'include' => 'nullable|string|max:255',
        ];
    }
}
