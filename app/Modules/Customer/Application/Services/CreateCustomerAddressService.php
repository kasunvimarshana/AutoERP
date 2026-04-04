<?php
namespace Modules\Customer\Application\Services;

use Modules\Customer\Application\Contracts\CreateCustomerAddressServiceInterface;
use Modules\Customer\Application\DTOs\CustomerAddressData;
use Modules\Customer\Domain\Entities\CustomerAddress;
use Modules\Customer\Domain\RepositoryInterfaces\CustomerAddressRepositoryInterface;

class CreateCustomerAddressService implements CreateCustomerAddressServiceInterface
{
    public function __construct(private readonly CustomerAddressRepositoryInterface $repository) {}

    public function execute(CustomerAddressData $data): CustomerAddress
    {
        return $this->repository->create($data->toArray());
    }
}
