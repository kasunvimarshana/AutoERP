<?php

declare(strict_types=1);

namespace Modules\HR\Application\Contracts\UseCases\Employees;

use Modules\Core\Application\Results\Result;

interface CreateEmployeeServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(array $payload): Result;
}