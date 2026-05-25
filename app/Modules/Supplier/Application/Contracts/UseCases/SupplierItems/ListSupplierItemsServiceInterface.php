<?php

declare(strict_types=1);

namespace Modules\Supplier\Application\Contracts\UseCases\SupplierItems;

use Modules\Core\Application\Results\Result;

interface ListSupplierItemsServiceInterface
{
    /**
     * @param array<string, mixed> $criteria
     */
    public function execute(array $criteria, int $perPage, int $page): Result;
}