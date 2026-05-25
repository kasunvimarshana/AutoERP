<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Contracts\UseCases\BankCategoryRules;

use Modules\Core\Application\Results\Result;

interface GetBankCategoryRuleServiceInterface
{
    public function execute(int|string $id): Result;
}
