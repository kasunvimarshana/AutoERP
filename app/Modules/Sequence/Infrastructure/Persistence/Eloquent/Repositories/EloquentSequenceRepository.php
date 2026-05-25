<?php

declare(strict_types=1);

namespace Modules\Sequence\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\Sequence\Application\Repositories\SequenceRepositoryInterface;
use Modules\Sequence\Infrastructure\Persistence\Eloquent\Models\SequenceModel;

class EloquentSequenceRepository extends EloquentRepository implements SequenceRepositoryInterface
{
    public function __construct(SequenceModel $model)
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

    public function paginateForOrganizationUnit(
        int|string $organizationUnitId,
        int $perPage = 15,
        array $with = [],
    ): LengthAwarePaginator {
        return $this->query($with)->where('organization_unit_id', $organizationUnitId)->paginate($perPage);
    }

    public function findForScopeDocumentAndPeriod(
        int|string $tenantId,
        int|string|null $organizationUnitId,
        string $documentType,
        ?string $periodValue,
        bool $fallbackToGlobal = true,
        array $with = [],
    ): ?Model {
        $query = $this->query($with)
            ->where('tenant_id', $tenantId)
            ->where('document_type', $documentType)
            ->where('period_value', $periodValue);

        if ($organizationUnitId === null) {
            return $query->whereNull('organization_unit_id')->first();
        }

        $scoped = (clone $query)->where('organization_unit_id', $organizationUnitId)->first();

        if ($scoped !== null || ! $fallbackToGlobal) {
            return $scoped;
        }

        return $query->whereNull('organization_unit_id')->first();
    }

    public function lockForScopeDocumentAndPeriod(
        int|string $tenantId,
        int|string|null $organizationUnitId,
        string $documentType,
        ?string $periodValue,
        bool $fallbackToGlobal = true,
        array $with = [],
    ): ?Model {
        $query = $this->query($with)
            ->where('tenant_id', $tenantId)
            ->where('document_type', $documentType)
            ->where('period_value', $periodValue);

        if ($organizationUnitId === null) {
            return $query
                ->whereNull('organization_unit_id')
                ->lockForUpdate()
                ->first();
        }

        $scoped = (clone $query)
            ->where('organization_unit_id', $organizationUnitId)
            ->lockForUpdate()
            ->first();

        if ($scoped !== null || ! $fallbackToGlobal) {
            return $scoped;
        }

        return $query
            ->whereNull('organization_unit_id')
            ->lockForUpdate()
            ->first();
    }

    public function findDefinitionForScopeDocument(
        int|string $tenantId,
        int|string|null $organizationUnitId,
        string $documentType,
        bool $fallbackToGlobal = true,
        array $with = [],
    ): ?Model {
        $query = $this->query($with)
            ->where('tenant_id', $tenantId)
            ->where('document_type', $documentType)
            ->orderByDesc('id');

        if ($organizationUnitId === null) {
            return $query->whereNull('organization_unit_id')->first();
        }

        $scoped = (clone $query)->where('organization_unit_id', $organizationUnitId)->first();

        if ($scoped !== null || ! $fallbackToGlobal) {
            return $scoped;
        }

        return $query->whereNull('organization_unit_id')->first();
    }
}

