<?php

declare(strict_types=1);

namespace Modules\Financial\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Collection;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Financial\Domain\Contracts\Repositories\AccountRepositoryInterface;
use Modules\Financial\Infrastructure\Persistence\Eloquent\Models\AccountModel;

class EloquentAccountRepository extends EloquentRepository implements AccountRepositoryInterface
{
    public function __construct(AccountModel $model)
    {
        parent::__construct($model);
    }

    /**
     * Find an account by its code within the current tenant scope.
     */
    public function findByCode(string $code): mixed
    {
        return $this->model->newQuery()->where('code', $code)->first();
    }

    /**
     * Get all accounts of a given type within the current tenant scope.
     */
    public function findByType(string $type): Collection
    {
        return $this->model->newQuery()->where('type', $type)->get();
    }
}
