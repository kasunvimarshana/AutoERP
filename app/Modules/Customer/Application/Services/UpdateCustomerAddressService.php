<?php

declare(strict_types=1);

namespace Modules\Customer\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Customer\Application\Contracts\UpdateCustomerAddressServiceInterface;
use Modules\Customer\Application\DTOs\CustomerAddressData;
use Modules\Customer\Domain\Entities\CustomerAddress;
use Modules\Customer\Domain\Exceptions\CustomerAddressNotFoundException;
use Modules\Customer\Domain\Exceptions\CustomerNotFoundException;
use Modules\Customer\Domain\RepositoryInterfaces\CustomerAddressRepositoryInterface;
use Modules\Customer\Domain\RepositoryInterfaces\CustomerRepositoryInterface;

class UpdateCustomerAddressService extends BaseService implements UpdateCustomerAddressServiceInterface
{
    public function __construct(
        private readonly CustomerAddressRepositoryInterface $customerAddressRepository,
        private readonly CustomerRepositoryInterface $customerRepository,
    ) {
        parent::__construct($customerAddressRepository);
    }

    protected function handle(array $data): CustomerAddress
    {
        $id = (int) ($data['id'] ?? 0);
        $address = $this->customerAddressRepository->find($id);
        if (! $address) {
            throw new CustomerAddressNotFoundException($id);
        }

        $dto = CustomerAddressData::fromArray($data);

        if ($address->getCustomerId() !== $dto->customer_id) {
            throw new CustomerAddressNotFoundException($id);
        }

        $customer = $this->customerRepository->find($dto->customer_id);
        if (! $customer || $customer->getTenantId() !== $address->getTenantId()) {
            throw new CustomerNotFoundException($dto->customer_id);
        }

        $address->update(
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
                excludeId: $id,
            );
        }

        return $this->customerAddressRepository->save($address);
    }
}
