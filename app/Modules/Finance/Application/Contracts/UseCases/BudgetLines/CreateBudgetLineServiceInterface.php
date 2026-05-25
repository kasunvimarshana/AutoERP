<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Contracts\UseCases\BudgetLines;

use Modules\Core\Application\Results\Result;

interface CreateBudgetLineServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(array $payload): Result;
}
