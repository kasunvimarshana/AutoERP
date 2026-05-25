<?php

declare(strict_types=1);

namespace Modules\Purchase\Application\Actions;

use Modules\Core\Application\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Model;

class PersistPurchaseRecordAction
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(BaseRepositoryInterface $repository, array $attributes): Model
    {
        return $repository->create($attributes);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(BaseRepositoryInterface $repository, Model|int|string $record, array $attributes): Model
    {
        return $repository->update($record, $attributes);
    }
}

