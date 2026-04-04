<?php
namespace Modules\Customer\Application\Services;

use Illuminate\Support\Facades\Event;
use Modules\Customer\Application\Contracts\CreateCustomerServiceInterface;
use Modules\Customer\Application\DTOs\CustomerData;
use Modules\Customer\Domain\Entities\Customer;
use Modules\Customer\Domain\Events\CustomerCreated;
use Modules\Customer\Domain\RepositoryInterfaces\CustomerRepositoryInterface;

class CreateCustomerService implements CreateCustomerServiceInterface
{
    public function __construct(private readonly CustomerRepositoryInterface $repository) {}

    public function execute(CustomerData $data): Customer
    {
        $customer = $this->repository->create($data->toArray());
        Event::dispatch(new CustomerCreated($customer->tenantId, $customer->id));
        return $customer;
    }
}
