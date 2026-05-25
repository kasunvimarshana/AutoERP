<?php

declare(strict_types=1);

namespace Modules\Pricing\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\Pricing\Application\Repositories\PriceListRepositoryInterface;
use Modules\Pricing\Infrastructure\Persistence\Eloquent\Models\PriceListModel;

class EloquentPriceListRepository extends EloquentRepository implements PriceListRepositoryInterface
{
    public function __construct(PriceListModel $model)
    {
        parent::__construct($model);
    }

    public function findByName(string $name, array $with = []): ?Model
    {
        return $this->query($with)->where('name', $name)->first();
    }

    public function getForTenant(int|string $tenantId, array $with = []): Collection
    {
        return $this->query($with)->where('tenant_id', $tenantId)->get();
    }

    public function paginateForTenant(int|string $tenantId, int $perPage = 15, array $with = []): LengthAwarePaginator
    {
        return $this->query($with)->where('tenant_id', $tenantId)->paginate($perPage);
    }

    public function findForTenantById(int|string $tenantId, int|string $id, array $with = []): ?Model
    {
        return $this->query($with)->where('tenant_id', $tenantId)->whereKey($id)->first();
    }

    public function getForOrganizationUnit(int|string $organizationUnitId, array $with = []): Collection
    {
        return $this->query($with)->where('organization_unit_id', $organizationUnitId)->get();
    }

    public function paginateForOrganizationUnit(int|string $organizationUnitId, int $perPage = 15, array $with = []): LengthAwarePaginator
    {
        return $this->query($with)->where('organization_unit_id', $organizationUnitId)->paginate($perPage);
    }

    public function getActive(array $with = []): Collection
    {
        return $this->query($with)->where('is_active', true)->get();
    }

    public function getInactive(array $with = []): Collection
    {
        return $this->query($with)->where('is_active', false)->get();
    }

    public function getActiveForTenantByType(int|string $tenantId, string $type, ?string $date = null, array $with = []): Collection
    {
        return $this->applyValidity(
            $this->query($with)
                ->where('tenant_id', $tenantId)
                ->where('type', $type)
                ->where('is_active', true),
            $date
        )
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();
    }

    public function findDefaultForTenantByType(int|string $tenantId, string $type, ?string $date = null, array $with = []): ?Model
    {
        return $this->applyValidity(
            $this->query($with)
                ->where('tenant_id', $tenantId)
                ->where('type', $type)
                ->where('is_active', true)
                ->where('is_default', true),
            $date
        )->first();
    }

    private function applyValidity(Builder $query, ?string $date): Builder
    {
        if ($date === null) {
            return $query;
        }

        return $query
            ->where(function (Builder $query) use ($date): void {
                $query->whereNull('valid_from')->orWhere('valid_from', '<=', $date);
            })
            ->where(function (Builder $query) use ($date): void {
                $query->whereNull('valid_to')->orWhere('valid_to', '>=', $date);
            });
    }
}

