<?php

declare(strict_types=1);

namespace Modules\Customer\Services;

use InvalidArgumentException;
use Modules\Customer\DTOs\CustomerAddressData;
use Modules\Customer\Models\Customer;
use Modules\Customer\Models\CustomerAddress;

final class CustomerAddressService
{
    public function create(Customer $customer, CustomerAddressData $data): CustomerAddress
    {
        if (trim($data->addressLine1) === '') {
            throw new InvalidArgumentException('Customer address line 1 is required.');
        }
        if ($data->isPrimary && $customer->addresses()
            ->where('address_type', $data->addressType->value)
            ->where('is_primary', true)
            ->exists()) {
            throw new InvalidArgumentException('Customer can have only one primary address per address type.');
        }

        return $customer->addresses()->create([
            'tenant_id' => $customer->tenant_id,
            'organization_unit_id' => $customer->organization_unit_id,
            'address_type' => $data->addressType,
            'address_line_1' => $data->addressLine1,
            'address_line_2' => $data->addressLine2,
            'city' => $data->city,
            'state' => $data->state,
            'postal_code' => $data->postalCode,
            'country' => $data->country,
            'is_primary' => $data->isPrimary,
            'is_active' => $data->isActive,
        ]);
    }

    public function update(Customer $customer, CustomerAddress $address, CustomerAddressData $data): CustomerAddress
    {
        $this->assertOwned($customer, $address);
        if (trim($data->addressLine1) === '') {
            throw new InvalidArgumentException('Customer address line 1 is required.');
        }
        if ($data->isPrimary && $customer->addresses()
            ->whereKeyNot($address->getKey())
            ->where('address_type', $data->addressType->value)
            ->where('is_primary', true)
            ->exists()) {
            throw new InvalidArgumentException('Customer can have only one primary address per address type.');
        }

        $address->fill([
            'address_type' => $data->addressType,
            'address_line_1' => $data->addressLine1,
            'address_line_2' => $data->addressLine2,
            'city' => $data->city,
            'state' => $data->state,
            'postal_code' => $data->postalCode,
            'country' => $data->country,
            'is_primary' => $data->isPrimary,
            'is_active' => $data->isActive,
        ])->save();

        return $address->refresh();
    }

    public function delete(Customer $customer, CustomerAddress $address): void
    {
        $this->assertOwned($customer, $address);
        $address->delete();
    }

    /**
     * @param  list<CustomerAddressData>  $addresses
     */
    public function replace(Customer $customer, array $addresses): void
    {
        $customer->addresses()->delete();
        foreach ($addresses as $address) {
            $this->create($customer, $address);
        }
    }

    private function assertOwned(Customer $customer, CustomerAddress $address): void
    {
        if ((int) $address->customer_id !== (int) $customer->getKey()) {
            throw new InvalidArgumentException('Customer address does not belong to the customer.');
        }
    }
}
