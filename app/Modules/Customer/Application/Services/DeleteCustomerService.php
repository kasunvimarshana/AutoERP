<?php

declare(strict_types=1);

namespace Modules\Customer\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Customer\Application\Contracts\DeleteCustomerServiceInterface;
use Modules\Customer\Domain\Events\CustomerDeleted;
use Modules\Customer\Domain\Exceptions\CustomerNotFoundException;
use Modules\Customer\Domain\RepositoryInterfaces\CustomerRepositoryInterface;

class DeleteCustomerService extends BaseService implements DeleteCustomerServiceInterface
{
    public function __construct(private readonly CustomerRepositoryInterface $customerRepository)
    {
        parent::__construct($customerRepository);
    }

    protected function handle(array $data): bool
    {
        $id       = $data['id'];
        $customer = $this->customerRepository->find($id);

        if (! $customer) {
            throw new CustomerNotFoundException($id);
        }

        $tenantId = $customer->getTenantId();
        $deleted  = $this->customerRepository->delete($id);

        if ($deleted) {
            $this->addEvent(new CustomerDeleted($id, $tenantId));
        }

        return $deleted;
    }
}
