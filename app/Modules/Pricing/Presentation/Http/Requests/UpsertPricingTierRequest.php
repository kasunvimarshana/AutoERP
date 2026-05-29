<?php

declare(strict_types=1);

namespace Modules\Pricing\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Pricing\Presentation\Http\Requests\Concerns\ResolvesPricingTenant;

final class UpsertPricingTierRequest extends FormRequest
{
    use ResolvesPricingTenant;

    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        $required = $this->isMethod('post') ? 'required' : 'sometimes';

        return [
            'tenant_id' => [$required, 'integer', 'min:1', 'exists:tenants,id'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'price_list_item_id' => ['nullable', 'integer', 'min:1'],
            'pricing_rule_id' => ['nullable', 'integer', 'min:1'],
            'discount_id' => ['nullable', 'integer', 'min:1'],
            'sequence' => ['nullable', 'integer', 'min:1'],
            'min_quantity' => [$required, 'numeric', 'min:0'],
            'max_quantity' => ['nullable', 'numeric', 'min:0', 'gte:min_quantity'],
            'uom_id' => ['nullable', 'integer', 'min:1'],
            'currency_id' => ['nullable', 'integer', 'min:1'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'adjustment_type' => ['nullable', 'string', 'in:percentage,fixed,override'],
            'adjustment_value' => ['nullable', 'numeric'],
            'priority' => ['nullable', 'integer', 'min:0'],
            'valid_from' => ['nullable', 'date'],
            'valid_to' => ['nullable', 'date', 'after_or_equal:valid_from'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
