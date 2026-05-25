<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Contracts\UseCases\BankCategoryRules;

use Modules\Core\Application\Results\Result;

interface ListBankCategoryRulesServiceInterface
{
    /**
     * @param array<string, mixed> $criteria
     */
    public function execute(array $criteria, int $perPage, int $page): Result;
}
