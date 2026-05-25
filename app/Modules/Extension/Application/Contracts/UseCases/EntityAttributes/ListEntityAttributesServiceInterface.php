<?php

declare(strict_types=1);

namespace Modules\Extension\Application\Contracts\UseCases\EntityAttributes;

use Modules\Core\Application\Results\Result;

interface ListEntityAttributesServiceInterface
{
    /**
     * @param array<string, mixed> $criteria
     */
    public function execute(array $criteria, int $perPage, int $page): Result;
}