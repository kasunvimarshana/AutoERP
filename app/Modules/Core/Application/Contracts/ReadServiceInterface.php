<?php

namespace Modules\Core\Application\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ReadServiceInterface
{
    /**
     * Find a single record by its primary key.
     *
     * @param mixed $id
     * @return mixed
     */
    public function find(mixed $id): mixed;

    /**
     * List records with filters, pagination, sorting and eager loading.
     *
     * @param array $filters
     * @param int|null $perPage
     * @param int $page
     * @param string|null $sort  Format: "column:direction"
     * @param string|null $include  Comma-separated list of relations to eager load
     * @return LengthAwarePaginator
     */
    public function list(
        array $filters = [],
        ?int $perPage = null,
        int $page = 1,
        ?string $sort = null,
        ?string $include = null
    ): LengthAwarePaginator;
}
