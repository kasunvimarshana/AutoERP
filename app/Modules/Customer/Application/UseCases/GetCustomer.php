<?php

declare(strict_types=1);

namespace Modules\Customer\Application\UseCases;

use Modules\Customer\Application\Contracts\CreateCustomerServiceInterface;
use Modules\Customer\Domain\Entities\Customer;

class GetCustomer
{
    public function __construct(private readonly CreateCustomerServiceInterface $service) {}

    public function execute(int $id): ?Customer
    {
        return $this->service->find($id);
    }
}
