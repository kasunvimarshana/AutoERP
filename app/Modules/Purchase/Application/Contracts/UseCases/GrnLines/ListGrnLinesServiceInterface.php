<?php

declare(strict_types=1);

namespace Modules\Purchase\Application\Contracts\UseCases\GrnLines;

use Modules\Core\Application\Results\Result;

interface ListGrnLinesServiceInterface
{
    /**
     * @param array<string, mixed> $criteria
     */
    public function execute(array $criteria, int $perPage, int $page): Result;
}