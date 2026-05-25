<?php

declare(strict_types=1);

namespace Modules\UOM\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\UOM\Application\Repositories\UomConversionRepositoryInterface;
use Modules\UOM\Infrastructure\Persistence\Eloquent\Models\UomConversionModel;

class EloquentUomConversionRepository extends EloquentRepository implements UomConversionRepositoryInterface
{
    public function __construct(UomConversionModel $model)
    {
        parent::__construct($model);
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

    public function findForScope(
        int|string $tenantId,
        int|string $fromUomId,
        int|string $toUomId,
        int|string|null $itemId = null,
        int|string|null $excludeId = null,
        array $with = []
    ): ?Model {
        $query = $this->query($with)
            ->where('tenant_id', $tenantId)
            ->where('from_uom_id', $fromUomId)
            ->where('to_uom_id', $toUomId)
            ->when($itemId === null, fn ($query) => $query->whereNull('item_id'))
            ->when($itemId !== null, fn ($query) => $query->where('item_id', $itemId));

        if ($excludeId !== null) {
            $query->whereKeyNot($excludeId);
        }

        return $query->first();
    }

    public function findActiveConversion(
        int|string $tenantId,
        int|string $fromUomId,
        int|string $toUomId,
        int|string|null $itemId = null,
        array $with = []
    ): ?Model {
        $specific = $this->findActiveConversionForItem($tenantId, $fromUomId, $toUomId, $itemId, $with);

        if ($specific !== null || $itemId === null) {
            return $specific;
        }

        return $this->findActiveConversionForItem($tenantId, $fromUomId, $toUomId, null, $with);
    }

    private function findActiveConversionForItem(
        int|string $tenantId,
        int|string $fromUomId,
        int|string $toUomId,
        int|string|null $itemId,
        array $with = []
    ): ?Model {
        return $this->query($with)
            ->where('tenant_id', $tenantId)
            ->where('from_uom_id', $fromUomId)
            ->where('to_uom_id', $toUomId)
            ->where('is_active', true)
            ->when($itemId === null, fn ($query) => $query->whereNull('item_id'))
            ->when($itemId !== null, fn ($query) => $query->where('item_id', $itemId))
            ->first();
    }
}

