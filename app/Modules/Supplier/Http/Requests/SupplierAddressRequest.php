<?php

declare(strict_types=1);

namespace Modules\Supplier\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Supplier\DTOs\SupplierAddressData;
use Modules\Supplier\Enums\SupplierAddressType;

abstract class SupplierAddressRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'address_type' => ['required', Rule::enum(SupplierAddressType::class)],
            'address_line_1' => ['required', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:120'],
            'state' => ['nullable', 'string', 'max:120'],
            'postal_code' => ['nullable', 'string', 'max:40'],
            'country' => ['nullable', 'string', 'max:120'],
            'is_primary' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function toData(): SupplierAddressData
    {
        return new SupplierAddressData(
            addressType: SupplierAddressType::from((string) $this->input('address_type')),
            addressLine1: (string) $this->input('address_line_1'),
            addressLine2: $this->nullableString('address_line_2'),
            city: $this->nullableString('city'),
            state: $this->nullableString('state'),
            postalCode: $this->nullableString('postal_code'),
            country: $this->nullableString('country'),
            isPrimary: $this->boolean('is_primary'),
            isActive: $this->boolean('is_active', true),
        );
    }

    private function nullableString(string $key): ?string
    {
        return $this->filled($key) ? (string) $this->input($key) : null;
    }
}
