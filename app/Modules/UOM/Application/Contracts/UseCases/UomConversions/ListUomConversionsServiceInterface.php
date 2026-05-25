<?php

declare(strict_types=1);

namespace Modules\UOM\Application\Contracts\UseCases\UomConversions;

use Modules\Core\Application\Results\Result;

interface ListUomConversionsServiceInterface
{
    /**
     * @param array<string, mixed> $criteria
     */
    public function execute(array $criteria, int $perPage, int $page): Result;
}