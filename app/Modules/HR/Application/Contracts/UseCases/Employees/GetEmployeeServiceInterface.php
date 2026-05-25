<?php

declare(strict_types=1);

namespace Modules\HR\Application\Contracts\UseCases\Employees;

use Modules\Core\Application\Results\Result;

interface GetEmployeeServiceInterface
{
    public function execute(int|string $id): Result;
}