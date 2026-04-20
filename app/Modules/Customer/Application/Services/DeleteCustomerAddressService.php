<?php

declare(strict_types=1);

namespace Modules\Customer\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Customer\Application\Contracts\DeleteCustomerAddressServiceInterface;
use Modules\Customer\Domain\Exceptions\CustomerAddressNotFoundException;
use Modules\Customer\Domain\RepositoryInterfaces\CustomerAddressRepositoryInterface;

class DeleteCustomerAddressService extends BaseService implements DeleteCustomerAddressServiceInterface
{
    public function __construct(private readonly CustomerAddressRepositoryInterface $customerAddressRepository)
    {
        parent::__construct($customerAddressRepository);
    }

    protected function handle(array $data): bool
    {
        $id = (int) ($data['id'] ?? 0);
        $address = $this->customerAddressRepository->find($id);

        if (! $address) {
            throw new CustomerAddressNotFoundException($id);
        }

        return $this->customerAddressRepository->delete($id);
    }
}
