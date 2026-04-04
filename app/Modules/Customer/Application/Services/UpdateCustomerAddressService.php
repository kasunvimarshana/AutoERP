<?php
namespace Modules\Customer\Application\Services;

use Modules\Customer\Application\Contracts\UpdateCustomerAddressServiceInterface;
use Modules\Customer\Application\DTOs\CustomerAddressData;
use Modules\Customer\Domain\Entities\CustomerAddress;
use Modules\Customer\Domain\RepositoryInterfaces\CustomerAddressRepositoryInterface;

class UpdateCustomerAddressService implements UpdateCustomerAddressServiceInterface
{
    public function __construct(private readonly CustomerAddressRepositoryInterface $repository) {}

    public function execute(int $id, CustomerAddressData $data): CustomerAddress
    {
        $address = $this->repository->findById($id);
        if (!$address) {
            throw new \DomainException("CustomerAddress not found: {$id}");
        }
        return $this->repository->update($address, $data->toArray());
    }
}
