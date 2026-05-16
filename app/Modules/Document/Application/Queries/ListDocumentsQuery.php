<?php

namespace Modules\Document\Application\Queries;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Document\Domain\Repositories\DocumentRepositoryInterface;

class ListDocumentsQuery
{
    public function __construct(
        private readonly DocumentRepositoryInterface $repository,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function execute(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginate($filters, $perPage);
    }
}
