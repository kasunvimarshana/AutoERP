<?php

declare(strict_types=1);

namespace Modules\HR\Application\Contracts\UseCases\EmployeeContracts;

use Modules\Core\Application\Results\Result;

interface DeleteEmployeeContractServiceInterface
{
    public function execute(int|string $id): Result;
}