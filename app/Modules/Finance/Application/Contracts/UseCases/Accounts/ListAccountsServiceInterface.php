<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Contracts\UseCases\Accounts;

use Modules\Core\Application\Results\Result;

interface ListAccountsServiceInterface
{
    /**
     * @param array<string, mixed> $criteria
     */
    public function execute(array $criteria, int $perPage, int $page): Result;
}
