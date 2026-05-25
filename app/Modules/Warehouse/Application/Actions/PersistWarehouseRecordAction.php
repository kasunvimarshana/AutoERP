<?php

declare(strict_types=1);

namespace Modules\Warehouse\Application\Actions;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Repositories\Contracts\RepositoryPortInterface;

class PersistWarehouseRecordAction
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(RepositoryPortInterface $repository, array $attributes): DataRecord
    {
        return $repository->transaction(fn (): DataRecord => $repository->create($attributes));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(RepositoryPortInterface $repository, int|string $id, array $attributes): DataRecord
    {
        return $repository->transaction(fn (): DataRecord => $repository->update($id, $attributes));
    }
}
