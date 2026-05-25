<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Contracts\UseCases\BankCategoryRules;

use Modules\Core\Application\Results\Result;

interface CreateBankCategoryRuleServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(array $payload): Result;
}
