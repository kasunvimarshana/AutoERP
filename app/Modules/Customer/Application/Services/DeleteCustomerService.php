<?php
namespace Modules\Customer\Application\Services;

use Illuminate\Support\Facades\Event;
use Modules\Customer\Application\Contracts\DeleteCustomerServiceInterface;
use Modules\Customer\Domain\Events\CustomerDeleted;
use Modules\Customer\Domain\RepositoryInterfaces\CustomerRepositoryInterface;

class DeleteCustomerService implements DeleteCustomerServiceInterface
{
    public function __construct(private readonly CustomerRepositoryInterface $repository) {}

    public function execute(int $id): bool
    {
        $customer = $this->repository->findById($id);
        if (!$customer) {
            throw new \DomainException("Customer not found: {$id}");
        }
        $result = $this->repository->delete($customer);
        if ($result) {
            Event::dispatch(new CustomerDeleted($customer->tenantId, $customer->id));
        }
        return $result;
    }
}
