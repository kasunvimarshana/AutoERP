<?php

declare(strict_types=1);

namespace Modules\Customer\Application\Contracts\UseCases\Customers;

use Modules\Core\Application\Results\Result;

interface GetCustomerServiceInterface
{
    public function execute(int|string $id): Result;
}