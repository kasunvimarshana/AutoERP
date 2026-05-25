<?php

declare(strict_types=1);

namespace Modules\Pricing\Application\Contracts\UseCases\CustomerPriceLists;

use Modules\Core\Application\Results\Result;

interface DeleteCustomerPriceListServiceInterface
{
    public function execute(int|string $id): Result;
}