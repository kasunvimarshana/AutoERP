<?php

declare(strict_types=1);

namespace Modules\HR\Application\Contracts\UseCases\LeavePolicyLines;

use Modules\Core\Application\Results\Result;

interface ListLeavePolicyLinesServiceInterface
{
    /**
     * @param array<string, mixed> $criteria
     */
    public function execute(array $criteria, int $perPage, int $page): Result;
}