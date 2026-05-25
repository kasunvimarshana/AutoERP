<?php

declare(strict_types=1);

namespace Modules\HR\Application\Contracts\UseCases\SalaryComponents;

use Modules\Core\Application\Results\Result;

interface CreateSalaryComponentServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(array $payload): Result;
}