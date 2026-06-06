<?php

declare(strict_types=1);

namespace Modules\Supplier\Services;

use InvalidArgumentException;
use Modules\Supplier\DTOs\SupplierContactData;
use Modules\Supplier\Models\Supplier;
use Modules\Supplier\Models\SupplierContact;

final class SupplierContactService
{
    public function create(Supplier $supplier, SupplierContactData $data): SupplierContact
    {
        if (trim($data->contactName) === '') {
            throw new InvalidArgumentException('Supplier contact name is required.');
        }
        if ($data->isPrimary && $supplier->contacts()->where('is_primary', true)->exists()) {
            throw new InvalidArgumentException('Supplier can have only one primary contact.');
        }

        return $supplier->contacts()->create([
            'tenant_id' => $supplier->tenant_id,
            'organization_unit_id' => $supplier->organization_unit_id,
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

    /**
     * @param  list<SupplierContactData>  $contacts
     */
    public function replace(Supplier $supplier, array $contacts): void
    {
        $supplier->contacts()->delete();
        foreach ($contacts as $contact) {
            $this->create($supplier, $contact);
        }
    }
}
