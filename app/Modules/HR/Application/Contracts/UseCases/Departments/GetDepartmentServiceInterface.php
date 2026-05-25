<?php

declare(strict_types=1);

namespace Modules\HR\Application\Contracts\UseCases\Departments;

use Modules\Core\Application\Results\Result;

interface GetDepartmentServiceInterface
{
    public function execute(int|string $id): Result;
}