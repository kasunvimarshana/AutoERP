<?php

declare(strict_types=1);

namespace Modules\Pricing\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Pricing\Presentation\Http\Requests\Concerns\ResolvesPricingTenant;

final class UpsertCustomerPriceListRequest extends FormRequest
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
            'customer_id' => array_merge($required, ['integer', 'min:1', 'exists:customers,id']),
            'price_list_id' => array_merge($required, ['integer', 'min:1', 'exists:price_lists,id']),
            'priority' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
