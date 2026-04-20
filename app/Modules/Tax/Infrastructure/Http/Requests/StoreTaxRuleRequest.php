<?php

declare(strict_types=1);

namespace Modules\Tax\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTaxRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = (int) $this->input('tenant_id');
        $taxGroupId = (int) $this->route('taxGroup');

        return [
            'tenant_id' => 'required|integer|exists:tenants,id',
            'tax_group_id' => [
                'sometimes',
                'integer',
                Rule::exists('tax_groups', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'product_category_id' => [
                'nullable',
                'integer',
                Rule::exists('product_categories', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'party_type' => 'nullable|in:customer,supplier',
            'region' => 'nullable|string|max:255',
            'priority' => 'nullable|integer|min:0',
            '_tax_group_from_route' => 'sometimes',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'tax_group_id' => $this->input('tax_group_id', (int) $this->route('taxGroup')),
        ]);
    }
}
