<?php

declare(strict_types=1);

namespace Modules\Customer\Application\Contracts;

use Modules\Core\Application\Contracts\ReadServiceInterface;

interface FindCustomerAddressServiceInterface extends ReadServiceInterface
{
    public function paginateByCustomer(int $tenantId, int $customerId, int $perPage = 15, int $page = 1): mixed;
}
