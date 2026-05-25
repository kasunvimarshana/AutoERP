<?php

declare(strict_types=1);

namespace Modules\Item\Application\Contracts\UseCases\ComboItems;

use Modules\Core\Application\Results\Result;

interface ListComboItemsServiceInterface
{
    /**
     * @param array<string, mixed> $criteria
     */
    public function execute(array $criteria, int $perPage, int $page): Result;
}
