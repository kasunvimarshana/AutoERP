<?php

declare(strict_types=1);

namespace Modules\Pricing\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Pricing\Presentation\Http\Requests\Concerns\ResolvesPricingTenant;

final class UpsertPriceListRequest extends FormRequest
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
            'code' => ['nullable', 'string', 'max:255'],
            'name' => array_merge($required, ['string', 'max:255']),
            'description' => ['nullable', 'string'],
            'type' => ['nullable', 'string', 'max:255'],
            'scope_type' => ['nullable', 'string', 'max:120'],
            'source_type' => ['nullable', 'string', 'max:120'],
            'source_id' => ['nullable', 'integer', 'min:1'],
            'currency_id' => ['nullable', 'integer', 'min:1', 'exists:currencies,id'],
            'priority' => ['nullable', 'integer', 'min:0'],
            'is_default' => ['nullable', 'boolean'],
            'is_stackable' => ['nullable', 'boolean'],
            'is_exclusive' => ['nullable', 'boolean'],
            'valid_from' => ['nullable', 'date'],
            'valid_to' => ['nullable', 'date', 'after_or_equal:valid_from'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
