<?php

declare(strict_types=1);

namespace Modules\Customer\Application\Contracts\UseCases\Customers;

use Modules\Core\Application\Results\Result;

interface ListCustomersServiceInterface
{
    /**
     * @param array<string, mixed> $criteria
     */
    public function execute(array $criteria, int $perPage, int $page): Result;
}