<?php

declare(strict_types=1);

namespace Modules\Rental\Application\Contracts;

use Modules\Rental\Domain\Entities\RentalExpense;

interface UpdateRentalExpenseServiceInterface
{
    public function execute(array $data): RentalExpense;
}
