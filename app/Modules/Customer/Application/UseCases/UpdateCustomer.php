<?php

declare(strict_types=1);

namespace Modules\Customer\Application\UseCases;

use Modules\Customer\Application\Contracts\UpdateCustomerServiceInterface;
use Modules\Customer\Domain\Entities\Customer;

class UpdateCustomer
{
    public function __construct(private readonly UpdateCustomerServiceInterface $service) {}

    public function execute(array $data): Customer
    {
        return $this->service->execute($data);
    }
}
