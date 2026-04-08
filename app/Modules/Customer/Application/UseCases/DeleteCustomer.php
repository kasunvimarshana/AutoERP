<?php

declare(strict_types=1);

namespace Modules\Customer\Application\UseCases;

use Modules\Customer\Application\Contracts\DeleteCustomerServiceInterface;

class DeleteCustomer
{
    public function __construct(private readonly DeleteCustomerServiceInterface $service) {}

    public function execute(int $id): bool
    {
        return $this->service->execute(['id' => $id]);
    }
}
