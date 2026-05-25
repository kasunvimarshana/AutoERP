<?php

declare(strict_types=1);

namespace Modules\Pricing\Application\Contracts\UseCases\SupplierPriceLists;

use Modules\Core\Application\Results\Result;

interface DeleteSupplierPriceListServiceInterface
{
    public function execute(int|string $id): Result;
}