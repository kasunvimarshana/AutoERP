<?php

declare(strict_types=1);

namespace Modules\Item\Application\Contracts\UseCases\ItemIdentifiers;

use Modules\Core\Application\Results\Result;

interface ListItemIdentifiersServiceInterface
{
    /**
     * @param array<string, mixed> $criteria
     */
    public function execute(array $criteria, int $perPage, int $page): Result;
}
