<?php
namespace Modules\Customer\Application\Services;

use Modules\Customer\Application\Contracts\DeleteCustomerAddressServiceInterface;
use Modules\Customer\Domain\RepositoryInterfaces\CustomerAddressRepositoryInterface;

class DeleteCustomerAddressService implements DeleteCustomerAddressServiceInterface
{
    public function __construct(private readonly CustomerAddressRepositoryInterface $repository) {}

    public function execute(int $id): bool
    {
        $address = $this->repository->findById($id);
        if (!$address) {
            throw new \DomainException("CustomerAddress not found: {$id}");
        }
        return $this->repository->delete($address);
    }
}
