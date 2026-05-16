<?php

declare(strict_types=1);

namespace Modules\Core\Domain\Contracts\Repositories;

interface CrudRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): mixed;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function updateById(int|string $id, array $attributes): mixed;

    public function findById(int|string $id): mixed;

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(
        array $filters = [],
        ?int $perPage = null,
        int $page = 1,
        ?string $sort = null,
        ?string $include = null,
    ): mixed;

    public function deleteById(int|string $id): bool;
}
