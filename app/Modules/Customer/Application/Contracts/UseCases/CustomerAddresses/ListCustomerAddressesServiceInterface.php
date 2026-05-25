<?php

declare(strict_types=1);

namespace Modules\Customer\Application\Contracts\UseCases\CustomerAddresses;

use Modules\Core\Application\Results\Result;

interface ListCustomerAddressesServiceInterface
{
    /**
     * @param array<string, mixed> $criteria
     */
    public function execute(array $criteria, int $perPage, int $page): Result;
}