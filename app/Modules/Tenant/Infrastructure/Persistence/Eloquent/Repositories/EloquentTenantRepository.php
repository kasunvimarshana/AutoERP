<?php

declare(strict_types=1);

namespace Modules\Tenant\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Tenant\Domain\RepositoryInterfaces\TenantRepositoryInterface;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\TenantModel;

final class EloquentTenantRepository extends EloquentRepository implements TenantRepositoryInterface
{
    public function __construct(TenantModel $model)
    {
        parent::__construct($model);
    }

    /**
     * {@inheritdoc}
     */
    public function findBySlug(string $slug): mixed
    {
        return $this->model->newQuery()
            ->where('slug', $slug)
            ->first();
    }

    /**
     * {@inheritdoc}
     */
    public function findByDomain(string $domain): mixed
    {
        return $this->model->newQuery()
            ->where('domain', $domain)
            ->first();
    }
}
