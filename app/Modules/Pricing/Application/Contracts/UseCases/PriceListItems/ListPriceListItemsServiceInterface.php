<?php

declare(strict_types=1);

namespace Modules\Pricing\Application\Contracts\UseCases\PriceListItems;

use Modules\Core\Application\Results\Result;

interface ListPriceListItemsServiceInterface
{
    /**
     * @param array<string, mixed> $criteria
     */
    public function execute(array $criteria, int $perPage, int $page): Result;
}