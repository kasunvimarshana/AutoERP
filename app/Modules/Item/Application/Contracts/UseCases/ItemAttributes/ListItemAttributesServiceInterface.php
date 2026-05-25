<?php

declare(strict_types=1);

namespace Modules\Item\Application\Contracts\UseCases\ItemAttributes;

use Modules\Core\Application\Results\Result;

interface ListItemAttributesServiceInterface
{
    /**
     * @param array<string, mixed> $criteria
     */
    public function execute(array $criteria, int $perPage, int $page): Result;
}
