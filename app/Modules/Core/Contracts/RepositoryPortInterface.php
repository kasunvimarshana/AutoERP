<?php

declare(strict_types=1);

namespace Modules\Core\Contracts;

use Modules\Core\DTOs\DataRecord;
use Modules\Core\DTOs\PagedResult;

interface RepositoryPortInterface
{
    public function findById(int|string $id, array $with = []): ?DataRecord;

    public function findOrFail(int|string $id, array $with = []): DataRecord;

    /**
     * @return list<DataRecord>
     */
    public function list(array $criteria = [], array $with = []): array;

    public function page(
        array $criteria,
        int $perPage,
        int $page,
        array $with = [],
    ): PagedResult;

    public function create(array $attributes): DataRecord;

    public function update(int|string $id, array $attributes): DataRecord;

    public function delete(int|string $id): bool;

    public function restore(int|string $id): bool;

    public function exists(array $criteria): bool;
}
