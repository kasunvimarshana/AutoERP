<?php

declare(strict_types=1);

namespace Modules\Supplier\Application\Contracts\UseCases\Suppliers;

use Modules\Core\Application\Results\Result;

interface ListSuppliersServiceInterface
{
    /**
     * @param array<string, mixed> $criteria
     */
    public function execute(array $criteria, int $perPage, int $page): Result;
}