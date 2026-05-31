<?php

declare(strict_types=1);

namespace Modules\Supplier\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpsertSupplierAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $aliases = [];

        if ($this->has('address_type') && ! $this->has('type')) {
            $aliases['type'] = $this->input('address_type');
        }

        if ($this->has('address_line_1') && ! $this->has('address_line1')) {
            $aliases['address_line1'] = $this->input('address_line_1');
        }

        if ($this->has('address_line_2') && ! $this->has('address_line2')) {
            $aliases['address_line2'] = $this->input('address_line_2');
        }

        if ($this->has('is_primary') && ! $this->has('is_default')) {
            $aliases['is_default'] = $this->input('is_primary');
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
            'tenant_id' => ['nullable', 'integer', 'min:1'],
            'row_version' => ['nullable', 'integer', 'min:0'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'metadata' => ['nullable', 'array'],
            'supplier_id' => array_merge($required, ['integer', 'min:1', 'exists:suppliers,id']),
            'type' => ['nullable', 'string', 'max:50'],
            'label' => ['nullable', 'string', 'max:120'],
            'contact_person' => ['nullable', 'string', 'max:180'],
            'contact_phone' => ['nullable', 'string', 'max:100'],
            'address_line1' => array_merge($required, ['string', 'max:255']),
            'address_line2' => ['nullable', 'string', 'max:255'],
            'city' => array_merge($required, ['string', 'max:120']),
            'state' => ['nullable', 'string', 'max:120'],
            'postal_code' => array_merge($required, ['string', 'max:60']),
            'country_id' => ['nullable', 'integer', 'min:1', 'exists:countries,id'],
            'is_default' => ['nullable', 'boolean'],
            'is_default_billing' => ['nullable', 'boolean'],
            'is_default_shipping' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'geo_lat' => ['nullable', 'numeric'],
            'geo_lng' => ['nullable', 'numeric'],
        ];
    }
}
