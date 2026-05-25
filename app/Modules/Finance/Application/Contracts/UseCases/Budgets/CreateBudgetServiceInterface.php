<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Contracts\UseCases\Budgets;

use Modules\Core\Application\Results\Result;

interface CreateBudgetServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(array $payload): Result;
}
