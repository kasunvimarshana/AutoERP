<?php

declare(strict_types=1);

namespace Modules\HR\Application\Contracts\UseCases\Departments;

use Modules\Core\Application\Results\Result;

interface CreateDepartmentServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(array $payload): Result;
}