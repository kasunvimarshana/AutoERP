<?php

declare(strict_types=1);

namespace Modules\Customer\Services;

use InvalidArgumentException;
use Modules\Customer\DTOs\CustomerContactData;
use Modules\Customer\Models\Customer;
use Modules\Customer\Models\CustomerContact;

final class CustomerContactService
{
    public function create(Customer $customer, CustomerContactData $data): CustomerContact
    {
        if (trim($data->contactName) === '') {
            throw new InvalidArgumentException('Customer contact name is required.');
        }
        if ($data->isPrimary && $customer->contacts()->where('is_primary', true)->exists()) {
            throw new InvalidArgumentException('Customer can have only one primary contact.');
        }

        return $customer->contacts()->create([
            'tenant_id' => $customer->tenant_id,
            'organization_unit_id' => $customer->organization_unit_id,
            'contact_name' => $data->contactName,
            'designation' => $data->designation,
            'department' => $data->department,
            'email' => $data->email,
            'phone' => $data->phone,
            'mobile' => $data->mobile,
            'is_primary' => $data->isPrimary,
            'is_active' => $data->isActive,
            'notes' => $data->notes,
        ]);
    }

    public function update(Customer $customer, CustomerContact $contact, CustomerContactData $data): CustomerContact
    {
        $this->assertOwned($customer, $contact);
        if (trim($data->contactName) === '') {
            throw new InvalidArgumentException('Customer contact name is required.');
        }
        if ($data->isPrimary && $customer->contacts()->whereKeyNot($contact->getKey())->where('is_primary', true)->exists()) {
            throw new InvalidArgumentException('Customer can have only one primary contact.');
        }

        $contact->fill([
            'contact_name' => $data->contactName,
            'designation' => $data->designation,
            'department' => $data->department,
            'email' => $data->email,
            'phone' => $data->phone,
            'mobile' => $data->mobile,
            'is_primary' => $data->isPrimary,
            'is_active' => $data->isActive,
            'notes' => $data->notes,
        ])->save();

        return $contact->refresh();
    }

    public function delete(Customer $customer, CustomerContact $contact): void
    {
        $this->assertOwned($customer, $contact);
        $contact->delete();
    }

    /**
     * @param  list<CustomerContactData>  $contacts
     */
    public function replace(Customer $customer, array $contacts): void
    {
        $customer->contacts()->delete();
        foreach ($contacts as $contact) {
            $this->create($customer, $contact);
        }
    }

    private function assertOwned(Customer $customer, CustomerContact $contact): void
    {
        if ((int) $contact->customer_id !== (int) $customer->getKey()) {
            throw new InvalidArgumentException('Customer contact does not belong to the customer.');
        }
    }
}
