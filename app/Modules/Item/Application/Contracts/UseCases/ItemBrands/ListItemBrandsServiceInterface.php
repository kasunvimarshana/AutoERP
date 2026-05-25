<?php

declare(strict_types=1);

namespace Modules\Item\Application\Contracts\UseCases\ItemBrands;

use Modules\Core\Application\Results\Result;

interface ListItemBrandsServiceInterface
{
    /**
     * @param array<string, mixed> $criteria
     */
    public function execute(array $criteria, int $perPage, int $page): Result;
}
