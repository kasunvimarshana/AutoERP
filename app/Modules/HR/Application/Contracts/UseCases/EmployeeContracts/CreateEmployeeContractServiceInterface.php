<?php

declare(strict_types=1);

namespace Modules\HR\Application\Contracts\UseCases\EmployeeContracts;

use Modules\Core\Application\Results\Result;

interface CreateEmployeeContractServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(array $payload): Result;
}