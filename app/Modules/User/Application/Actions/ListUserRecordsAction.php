<?php

declare(strict_types=1);

namespace Modules\User\Application\Actions;

use Modules\Core\Application\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class ListUserRecordsAction
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

        if ($criteria !== []) {
            return $repository->getWhere($criteria);
        }

        return $repository->all();
    }
}

