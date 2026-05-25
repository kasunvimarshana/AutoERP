<?php

declare(strict_types=1);

namespace Modules\Supplier\Application\Contracts\UseCases\SupplierContacts;

use Modules\Core\Application\Results\Result;

interface ListSupplierContactsServiceInterface
{
    /**
     * @param array<string, mixed> $criteria
     */
    public function execute(array $criteria, int $perPage, int $page): Result;
}