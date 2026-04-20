<?php

declare(strict_types=1);

namespace Modules\Customer\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Customer\Application\Contracts\DeleteCustomerContactServiceInterface;
use Modules\Customer\Domain\Exceptions\CustomerContactNotFoundException;
use Modules\Customer\Domain\RepositoryInterfaces\CustomerContactRepositoryInterface;

class DeleteCustomerContactService extends BaseService implements DeleteCustomerContactServiceInterface
{
    public function __construct(private readonly CustomerContactRepositoryInterface $customerContactRepository)
    {
        parent::__construct($customerContactRepository);
    }

    protected function handle(array $data): bool
    {
        $id = (int) ($data['id'] ?? 0);
        $contact = $this->customerContactRepository->find($id);

        if (! $contact) {
            throw new CustomerContactNotFoundException($id);
        }

        return $this->customerContactRepository->delete($id);
    }
}
