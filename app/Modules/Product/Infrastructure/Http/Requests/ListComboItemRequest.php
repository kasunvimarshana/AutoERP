<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListComboItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => 'nullable|integer|exists:tenants,id',
            'combo_product_id' => 'nullable|integer|exists:products,id',
            'component_product_id' => 'nullable|integer|exists:products,id',
            'is_optional' => 'nullable|boolean',
            'sort' => 'nullable|string|max:60',
            'per_page' => 'nullable|integer|min:1|max:200',
            'page' => 'nullable|integer|min:1',
        ];
    }
}
