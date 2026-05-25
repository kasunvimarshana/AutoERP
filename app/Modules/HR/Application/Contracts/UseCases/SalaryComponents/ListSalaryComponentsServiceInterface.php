<?php

declare(strict_types=1);

namespace Modules\HR\Application\Contracts\UseCases\SalaryComponents;

use Modules\Core\Application\Results\Result;

interface ListSalaryComponentsServiceInterface
{
    /**
     * @param array<string, mixed> $criteria
     */
    public function execute(array $criteria, int $perPage, int $page): Result;
}