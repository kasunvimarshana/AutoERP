<?php

declare(strict_types=1);

namespace Modules\HR\Application\Contracts\UseCases\Shifts;

use Modules\Core\Application\Results\Result;

interface ListShiftsServiceInterface
{
    /**
     * @param array<string, mixed> $criteria
     */
    public function execute(array $criteria, int $perPage, int $page): Result;
}