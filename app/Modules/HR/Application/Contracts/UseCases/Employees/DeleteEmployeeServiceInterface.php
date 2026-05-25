<?php

declare(strict_types=1);

namespace Modules\HR\Application\Contracts\UseCases\Employees;

use Modules\Core\Application\Results\Result;

interface DeleteEmployeeServiceInterface
{
    public function execute(int|string $id): Result;
}