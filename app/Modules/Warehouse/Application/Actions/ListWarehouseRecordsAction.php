<?php

declare(strict_types=1);

namespace Modules\Warehouse\Application\Actions;

use Modules\Core\Application\DTO\PagedResult;
use Modules\Core\Application\Repositories\Contracts\RepositoryPortInterface;

class ListWarehouseRecordsAction
{
    /**
     * @param  array<string, mixed>  $criteria
     */
    public function execute(
        RepositoryPortInterface $repository,
        array $criteria = [],
        ?int $perPage = null,
    ): array|PagedResult {
        if ($perPage !== null) {
            return $repository->page($criteria, $perPage, 1);
        }

        return $repository->list($criteria);
    }
}
