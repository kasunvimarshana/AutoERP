<?php

declare(strict_types=1);

namespace Modules\Tax\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListTaxRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => 'required|integer|exists:tenants,id',
            'product_category_id' => 'nullable|integer',
            'party_type' => 'nullable|in:customer,supplier',
            'region' => 'nullable|string|max:255',
            'priority' => 'nullable|integer|min:0',
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
            'sort' => 'nullable|string|max:100',
        ];
    }
}
