<?php

declare(strict_types=1);

namespace Modules\Pricing\Application\Contracts;

use Modules\Core\Application\Contracts\ReadServiceInterface;

interface FindCustomerPriceListServiceInterface extends ReadServiceInterface
{
    public function paginateByCustomer(int $tenantId, int $customerId, int $perPage = 15, int $page = 1): mixed;
}
