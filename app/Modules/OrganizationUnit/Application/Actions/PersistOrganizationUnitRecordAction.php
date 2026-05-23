<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Application\Actions;

use App\Support\Repositories\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Model;

class PersistOrganizationUnitRecordAction
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(BaseRepositoryInterface $repository, array $attributes): Model
    {
        return $repository->transaction(fn (): Model => $repository->create($attributes));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(BaseRepositoryInterface $repository, Model|int|string $record, array $attributes): Model
    {
        return $repository->transaction(fn (): Model => $repository->update($record, $attributes));
    }
}
