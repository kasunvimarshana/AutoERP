<?php

declare(strict_types=1);

namespace Modules\Pricing\Application\Contracts\UseCases\SupplierPriceLists;

use Modules\Core\Application\Results\Result;

interface GetSupplierPriceListServiceInterface
{
    public function execute(int|string $id): Result;
}