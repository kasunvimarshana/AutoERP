<?php

declare(strict_types=1);

namespace Modules\Tenant\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Collection;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Tenant\Domain\Entities\TenantDomain;
use Modules\Tenant\Domain\RepositoryInterfaces\TenantDomainRepositoryInterface;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\TenantDomainModel;

class EloquentTenantDomainRepository extends EloquentRepository implements TenantDomainRepositoryInterface
{
    public function __construct(TenantDomainModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (TenantDomainModel $model): TenantDomain => $this->mapModelToDomainEntity($model));
    }

    public function findByDomain(string $domain): ?TenantDomain
    {
        $model = $this->model->where('domain', $domain)->first();

        return $model ? $this->toDomainEntity($model) : null;
    }

    public function findByTenantAndDomain(int $tenantId, string $domain): ?TenantDomain
    {
        $model = $this->model
            ->where('tenant_id', $tenantId)
            ->where('domain', $domain)
            ->first();

        return $model ? $this->toDomainEntity($model) : null;
    }

    public function getByTenant(int $tenantId, ?bool $isVerified = null, ?bool $isPrimary = null): iterable
    {
        $query = $this->model->where('tenant_id', $tenantId);

        if ($isVerified !== null) {
            $query->where('is_verified', $isVerified);
        }

        if ($isPrimary !== null) {
            $query->where('is_primary', $isPrimary);
        }

        /** @var Collection<int, TenantDomainModel> $models */
        $models = $query->orderBy('id')->get();

        return $this->toDomainCollection($models);
    }

    public function save(TenantDomain $tenantDomain): TenantDomain
    {
        $data = [
            'tenant_id' => $tenantDomain->getTenantId(),
            'domain' => $tenantDomain->getDomain(),
            'is_primary' => $tenantDomain->isPrimary(),
            'is_verified' => $tenantDomain->isVerified(),
            'verified_at' => $tenantDomain->getVerifiedAt(),
        ];

        if ($tenantDomain->getId()) {
            $model = $this->update($tenantDomain->getId(), $data);
        } else {
            $model = $this->create($data);
        }

        /** @var TenantDomainModel $model */

        return $this->mapModelToDomainEntity($model);
    }

    /**
     * {@inheritdoc}
     */
    public function find(int|string $id, array $columns = ['*']): ?TenantDomain
    {
        return parent::find($id, $columns);
    }

    private function mapModelToDomainEntity(TenantDomainModel $model): TenantDomain
    {
        return new TenantDomain(
            tenantId: $model->tenant_id,
            domain: $model->domain,
            isPrimary: (bool) $model->is_primary,
            isVerified: (bool) $model->is_verified,
            verifiedAt: $model->verified_at,
            id: $model->id,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at,
        );
    }
}
