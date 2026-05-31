<?php

declare(strict_types=1);

namespace Modules\Pricing\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Pricing\Presentation\Http\Requests\Concerns\ResolvesPricingTenant;

final class UpsertPriceListItemRequest extends FormRequest
{
    use ResolvesPricingTenant;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $required = $this->isMethod('post') ? ['required'] : ['sometimes'];

        return [
            'tenant_id' => array_merge($required, ['integer', 'min:1', 'exists:tenants,id']),
            'row_version' => ['nullable', 'integer', 'min:0'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1', 'exists:organization_units,id'],
            'metadata' => ['nullable', 'array'],
            'price_list_id' => array_merge($required, ['integer', 'min:1', 'exists:price_lists,id']),
            'item_id' => array_merge($required, ['integer', 'min:1', 'exists:items,id']),
            'variant_id' => ['nullable', 'integer', 'min:1', 'exists:item_variants,id'],
            'uom_id' => array_merge($required, ['integer', 'min:1', 'exists:unit_of_measures,id']),
            'currency_id' => ['nullable', 'integer', 'min:1', 'exists:currencies,id'],
            'party_type' => ['nullable', 'string', 'max:120'],
            'party_id' => ['nullable', 'integer', 'min:1'],
            'source_type' => ['nullable', 'string', 'max:120'],
            'source_id' => ['nullable', 'integer', 'min:1'],
            'min_quantity' => ['nullable', 'numeric'],
            'max_quantity' => ['nullable', 'numeric', 'gte:min_quantity'],
            'price' => array_merge($required, ['numeric']),
            'discount_type' => ['nullable', 'string', 'max:255'],
            'discount_value' => ['nullable', 'numeric'],
            'markup_type' => ['nullable', 'string', 'max:120'],
            'markup_value' => ['nullable', 'numeric'],
            'is_tax_inclusive' => ['nullable', 'boolean'],
            'priority' => ['nullable', 'integer', 'min:0'],
            'valid_from' => ['nullable', 'date'],
            'valid_to' => ['nullable', 'date', 'after_or_equal:valid_from'],
            'is_promotional' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'reference' => ['nullable', 'string', 'max:255'],
        ];
    }
}
