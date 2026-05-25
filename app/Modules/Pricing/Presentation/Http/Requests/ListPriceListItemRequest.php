<?php

declare(strict_types=1);

namespace Modules\Pricing\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ListPriceListItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'tenant_id' => ['nullable', 'integer', 'min:1', 'exists:tenants,id'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1', 'exists:organization_units,id'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:' . (int) config('pricing.pagination.max_per_page', 200)],
            'price_list_id' => ['nullable', 'integer', 'min:1', 'exists:price_lists,id'],
            'item_id' => ['nullable', 'integer', 'min:1', 'exists:items,id'],
            'variant_id' => ['nullable', 'integer', 'min:1', 'exists:item_variants,id'],
            'uom_id' => ['nullable', 'integer', 'min:1', 'exists:unit_of_measures,id']
        ];
    }
}