<?php

declare(strict_types=1);

namespace Modules\HR\Application\Contracts\UseCases\EmployeeContacts;

use Modules\Core\Application\Results\Result;

interface GetEmployeeContactServiceInterface
{
    public function execute(int|string $id): Result;
}