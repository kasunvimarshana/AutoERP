<?php

declare(strict_types=1);

namespace Modules\Item\Application\Contracts\UseCases\ItemVariants;

use Modules\Core\Application\Results\Result;

interface ListItemVariantsServiceInterface
{
    /**
     * @param array<string, mixed> $criteria
     */
    public function execute(array $criteria, int $perPage, int $page): Result;
}
