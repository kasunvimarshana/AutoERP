<?php

declare(strict_types=1);

namespace Modules\Pricing\Application\Contracts;

use Modules\Core\Application\Contracts\ReadServiceInterface;

interface FindPriceListItemServiceInterface extends ReadServiceInterface
{
    public function paginateByPriceList(int $tenantId, int $priceListId, int $perPage = 15, int $page = 1): mixed;
}
