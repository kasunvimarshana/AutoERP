<?php

declare(strict_types=1);

namespace Modules\Customer\Application\UseCases;

use Modules\Customer\Application\Contracts\CreateCustomerServiceInterface;
use Modules\Customer\Domain\Entities\Customer;

class CreateCustomer
{
    public function __construct(private readonly CreateCustomerServiceInterface $service) {}

    public function execute(array $data): Customer
    {
        return $this->service->execute($data);
    }
}
