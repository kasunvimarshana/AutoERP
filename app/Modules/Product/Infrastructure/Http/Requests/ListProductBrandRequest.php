<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListProductBrandRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => 'nullable|integer|min:1',
            'parent_id' => 'nullable|integer|min:1',
            'name' => 'nullable|string|max:255',
            'slug' => 'nullable|string|max:255',
            'code' => 'nullable|string|max:100',
            'is_active' => 'nullable|boolean',
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
            'sort' => 'nullable|string|max:50',
        ];
    }
}
