<?php

declare(strict_types=1);

namespace Modules\Sales\Application\Actions;

use Modules\Core\Application\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class ListSalesRecordsAction
{
    /**
     * @param  array<string, mixed>  $criteria
     */
    public function execute(
        BaseRepositoryInterface $repository,
        array $criteria = [],
        ?int $perPage = null,
    ): Collection|LengthAwarePaginator {
        if ($perPage !== null) {
            return $criteria === []
                ? $repository->paginate($perPage)
                : $repository->paginateWhere($criteria, $perPage);
        }

        return $criteria === []
            ? $repository->all()
            : $repository->getWhere($criteria);
    }
}

