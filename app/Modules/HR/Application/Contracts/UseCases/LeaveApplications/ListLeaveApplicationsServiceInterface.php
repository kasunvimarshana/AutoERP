<?php

declare(strict_types=1);

namespace Modules\HR\Application\Contracts\UseCases\LeaveApplications;

use Modules\Core\Application\Results\Result;

interface ListLeaveApplicationsServiceInterface
{
    /**
     * @param array<string, mixed> $criteria
     */
    public function execute(array $criteria, int $perPage, int $page): Result;
}