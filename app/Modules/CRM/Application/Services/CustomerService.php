<?php

declare(strict_types=1);

namespace Modules\CRM\Application\Services;

use Illuminate\Support\Facades\DB;
use Modules\Core\Application\Services\BaseService;
use Modules\CRM\Application\Contracts\CustomerServiceInterface;
use Modules\CRM\Domain\Contracts\Repositories\CustomerRepositoryInterface;
use Modules\CRM\Domain\Events\CustomerCreated;
use Modules\CRM\Domain\Exceptions\CustomerNotFoundException;

class CustomerService extends BaseService implements CustomerServiceInterface
{
    public function __construct(CustomerRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Default execute handler — delegates to createCustomer.
     */
    protected function handle(array $data): mixed
    {
        return $this->createCustomer($data);
    }

    /**
     * Create a new customer and dispatch a domain event.
     */
    public function createCustomer(array $data): mixed
    {
        return DB::transaction(function () use ($data) {
            $customer = $this->repository->create($data);
            $this->addEvent(new CustomerCreated((int) ($customer->tenant_id ?? 0), $customer->id));
            $this->dispatchEvents();

            return $customer;
        });
    }

    /**
     * Update an existing customer.
     */
    public function updateCustomer(string $id, array $data): mixed
    {
        return DB::transaction(function () use ($id, $data) {
            $customer = $this->repository->find($id);
            if (! $customer) {
                throw new CustomerNotFoundException($id);
            }

            return $this->repository->update($id, $data);
        });
    }
}
