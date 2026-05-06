<?php

declare(strict_types=1);

namespace Modules\Customer\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Customer\Application\Contracts\CreateCustomerAddressServiceInterface;
use Modules\Customer\Application\DTOs\CustomerAddressData;
use Modules\Customer\Domain\Entities\CustomerAddress;
use Modules\Customer\Domain\Exceptions\CustomerNotFoundException;
use Modules\Customer\Domain\RepositoryInterfaces\CustomerAddressRepositoryInterface;
use Modules\Customer\Domain\RepositoryInterfaces\CustomerRepositoryInterface;

class CreateCustomerAddressService extends BaseService implements CreateCustomerAddressServiceInterface
{
    public function __construct(
        private readonly CustomerAddressRepositoryInterface $customerAddressRepository,
        private readonly CustomerRepositoryInterface $customerRepository,
    ) {
        parent::__construct($customerAddressRepository);
    }

    protected function handle(array $data): CustomerAddress
    {
        $dto = CustomerAddressData::fromArray($data);

        $customer = $this->customerRepository->find($dto->customer_id);
        if (! $customer) {
            throw new CustomerNotFoundException($dto->customer_id);
        }

        $address = new CustomerAddress(
            tenantId: $customer->getTenantId(),
            customerId: $dto->customer_id,
            type: $dto->type,
            label: $dto->label,
            addressLine1: $dto->address_line1,
            addressLine2: $dto->address_line2,
            city: $dto->city,
            state: $dto->state,
            postalCode: $dto->postal_code,
            countryId: $dto->country_id,
            isDefault: $dto->is_default,
            geoLat: $dto->geo_lat,
            geoLng: $dto->geo_lng,
        );

        if ($dto->is_default) {
            $this->customerAddressRepository->clearDefaultByCustomerAndType(
                tenantId: $customer->getTenantId(),
                customerId: $dto->customer_id,
                type: $dto->type,
            );
        }

        return $this->customerAddressRepository->save($address);
    }
}
