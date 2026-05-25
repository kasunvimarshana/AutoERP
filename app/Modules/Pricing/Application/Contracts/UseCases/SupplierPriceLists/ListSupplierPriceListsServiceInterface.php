<?php

declare(strict_types=1);

namespace Modules\Pricing\Application\Contracts\UseCases\SupplierPriceLists;

use Modules\Core\Application\Results\Result;

interface ListSupplierPriceListsServiceInterface
{
    /**
     * @param array<string, mixed> $criteria
     */
    public function execute(array $criteria, int $perPage, int $page): Result;
}