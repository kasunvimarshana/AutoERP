<?php
namespace Modules\Customer\Application\Services;

use Illuminate\Support\Facades\Event;
use Modules\Customer\Application\Contracts\UpdateCustomerServiceInterface;
use Modules\Customer\Application\DTOs\CustomerData;
use Modules\Customer\Domain\Entities\Customer;
use Modules\Customer\Domain\Events\CustomerUpdated;
use Modules\Customer\Domain\RepositoryInterfaces\CustomerRepositoryInterface;

class UpdateCustomerService implements UpdateCustomerServiceInterface
{
    public function __construct(private readonly CustomerRepositoryInterface $repository) {}

    public function execute(int $id, CustomerData $data): Customer
    {
        $customer = $this->repository->findById($id);
        if (!$customer) {
            throw new \DomainException("Customer not found: {$id}");
        }
        $updated = $this->repository->update($customer, $data->toArray());
        Event::dispatch(new CustomerUpdated($updated->tenantId, $updated->id));
        return $updated;
    }
}
