<?php

declare(strict_types=1);

namespace Modules\Customer\Application\Contracts\UseCases\CustomerAddresses;

use Modules\Core\Application\Results\Result;

interface DeleteCustomerAddressServiceInterface
{
    public function execute(int|string $id): Result;
}