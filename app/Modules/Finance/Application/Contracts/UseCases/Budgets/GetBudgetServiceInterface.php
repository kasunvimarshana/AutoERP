<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Contracts\UseCases\Budgets;

use Modules\Core\Application\Results\Result;

interface GetBudgetServiceInterface
{
    public function execute(int|string $id): Result;
}
