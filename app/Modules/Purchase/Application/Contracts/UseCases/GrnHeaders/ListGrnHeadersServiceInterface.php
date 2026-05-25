<?php

declare(strict_types=1);

namespace Modules\Purchase\Application\Contracts\UseCases\GrnHeaders;

use Modules\Core\Application\Results\Result;

interface ListGrnHeadersServiceInterface
{
    /**
     * @param array<string, mixed> $criteria
     */
    public function execute(array $criteria, int $perPage, int $page): Result;
}