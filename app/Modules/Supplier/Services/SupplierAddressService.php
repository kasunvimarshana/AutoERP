<?php

declare(strict_types=1);

namespace Modules\Supplier\Services;

use InvalidArgumentException;
use Modules\Supplier\DTOs\SupplierAddressData;
use Modules\Supplier\Models\Supplier;
use Modules\Supplier\Models\SupplierAddress;

final class SupplierAddressService
{
    public function create(Supplier $supplier, SupplierAddressData $data): SupplierAddress
    {
        if (trim($data->addressLine1) === '') {
            throw new InvalidArgumentException('Supplier address line 1 is required.');
        }
        if ($data->isPrimary && $supplier->addresses()
            ->where('address_type', $data->addressType->value)
            ->where('is_primary', true)
            ->exists()) {
            throw new InvalidArgumentException('Supplier can have only one primary address per address type.');
        }

        return $supplier->addresses()->create([
            'tenant_id' => $supplier->tenant_id,
            'organization_unit_id' => $supplier->organization_unit_id,
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

    /**
     * @param  list<SupplierAddressData>  $addresses
     */
    public function replace(Supplier $supplier, array $addresses): void
    {
        $supplier->addresses()->delete();
        foreach ($addresses as $address) {
            $this->create($supplier, $address);
        }
    }
}
