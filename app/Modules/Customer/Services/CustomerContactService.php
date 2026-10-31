<?php

declare(strict_types=1);

namespace Modules\Customer\Services;

use InvalidArgumentException;
use Modules\Customer\DTOs\CustomerContactData;
use Modules\Customer\Models\Customer;
use Modules\Customer\Models\CustomerContact;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class CustomerContactService
{
    public function __construct(private readonly CustomerPhoneService $phones) {}

    public function create(Customer $customer, CustomerContactData $data, string $fieldPrefix = ''): CustomerContact
    {
        return $this->phones->write((int) $customer->tenant_id, function () use ($customer, $data, $fieldPrefix): CustomerContact {
            $this->phones->assertAvailable((int) $customer->tenant_id, $customer->organization_unit_id, (int) $customer->id, [
                $fieldPrefix.'phone' => $data->phone,
                $fieldPrefix.'mobile' => $data->mobile,
            ]);
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
            ])->refresh();
        });
    }

    public function update(Customer $customer, CustomerContact $contact, CustomerContactData $data): CustomerContact
    {
        return $this->phones->write((int) $customer->tenant_id, function () use ($customer, $contact, $data): CustomerContact {
            $this->assertOwned($customer, $contact);
            $contact = $customer->contacts()->whereKey($contact->id)->lockForUpdate()->firstOrFail();
            if ($data->rowVersion !== (int) $contact->row_version) {
                throw new ConflictHttpException('Customer contact was changed by someone else. Reload before saving.');
            }
            $this->phones->assertAvailable((int) $customer->tenant_id, $customer->organization_unit_id, (int) $customer->id, ['phone' => $data->phone, 'mobile' => $data->mobile]);
            if (trim($data->contactName) === '') {
                throw new InvalidArgumentException('Customer contact name is required.');
            }
            if ($data->isPrimary && $customer->contacts()->whereKeyNot($contact->getKey())->where('is_primary', true)->exists()) {
                throw new InvalidArgumentException('Customer can have only one primary contact.');
            }

            $contact->fill([
                'row_version' => ((int) $contact->row_version) + 1,
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
        });
    }

    public function delete(Customer $customer, CustomerContact $contact, int $rowVersion): void
    {
        $this->phones->write((int) $customer->tenant_id, function () use ($customer, $contact, $rowVersion): void {
            $this->assertOwned($customer, $contact);
            $contact = $customer->contacts()->whereKey($contact->id)->lockForUpdate()->firstOrFail();
            if ($rowVersion !== (int) $contact->row_version) {
                throw new ConflictHttpException('Customer contact was changed by someone else. Reload before deleting.');
            }
            $contact->delete();
        });
    }

    /**
     * @param  list<CustomerContactData>  $contacts
     */
    public function replace(Customer $customer, array $contacts): void
    {
        $this->phones->write((int) $customer->tenant_id, function () use ($customer, $contacts): void {
            $customer->contacts()->delete();
            foreach ($contacts as $index => $contact) {
                $this->create($customer, $contact, "contacts.{$index}.");
            }
        });
    }

    private function assertOwned(Customer $customer, CustomerContact $contact): void
    {
        if ((int) $contact->customer_id !== (int) $customer->getKey()) {
            throw new InvalidArgumentException('Customer contact does not belong to the customer.');
        }
    }
}
