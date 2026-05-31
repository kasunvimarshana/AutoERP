<?php

declare(strict_types=1);

namespace Modules\Customer\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpsertCustomerAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $aliases = [];

        if ($this->has('type') && ! $this->has('address_type')) {
            $aliases['address_type'] = $this->input('type');
        }

        if ($this->has('address_line1') && ! $this->has('address_line_1')) {
            $aliases['address_line_1'] = $this->input('address_line1');
        }

        if ($this->has('address_line2') && ! $this->has('address_line_2')) {
            $aliases['address_line_2'] = $this->input('address_line2');
        }

        if ($this->has('state') && ! $this->has('state_province')) {
            $aliases['state_province'] = $this->input('state');
        }

        if ($this->has('is_default') && ! $this->has('is_primary')) {
            $aliases['is_primary'] = $this->boolean('is_default');
        }

        if ($aliases !== []) {
            $this->merge($aliases);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $required = $this->isMethod('post') ? ['required'] : ['sometimes'];

        return [
            'tenant_id' => ['nullable', 'integer', 'min:1', 'exists:tenants,id'],
            'row_version' => ['nullable', 'integer', 'min:0'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1', 'exists:organization_units,id'],
            'metadata' => ['nullable', 'array'],
            'customer_id' => array_merge($required, ['integer', 'min:1', 'exists:customers,id']),
            'address_type' => ['nullable', 'string', 'max:60'],
            'label' => ['nullable', 'string', 'max:120'],
            'contact_person' => ['nullable', 'string', 'max:180'],
            'contact_phone' => ['nullable', 'string', 'max:100'],
            'address_line_1' => array_merge($required, ['string', 'max:255']),
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'city' => array_merge($required, ['string', 'max:120']),
            'state_province' => ['nullable', 'string', 'max:120'],
            'postal_code' => array_merge($required, ['string', 'max:60']),
            'country_id' => ['nullable', 'integer', 'min:1', 'exists:countries,id'],
            'country_name' => ['nullable', 'string', 'max:120'],
            'is_primary' => ['nullable', 'boolean'],
            'is_primary_billing' => ['nullable', 'boolean'],
            'is_primary_shipping' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'geo_lat' => ['nullable', 'numeric'],
            'geo_lng' => ['nullable', 'numeric'],
        ];
    }
}
