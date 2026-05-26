<?php

declare(strict_types=1);

namespace Modules\Sequence\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Database\Eloquent\Model;
use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\DTO\PagedResult;
use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Sequence\Application\Repositories\SequenceRepositoryInterface;
use Modules\Sequence\Infrastructure\Persistence\Eloquent\Models\SequenceModel;

final class EloquentSequenceRepository extends EloquentRepository implements SequenceRepositoryInterface
{
    public function __construct(SequenceModel $model)
    {
        parent::__construct($model);
    }

    public function findByScope(
        int $tenantId,
        ?int $organizationUnitId,
        string $documentType,
        ?string $periodValue,
    ): ?DataRecord {
        $query = $this->query()
            ->where('tenant_id', $tenantId)
            ->where('document_type', trim($documentType));

        if ($organizationUnitId === null) {
            $query->whereNull('organization_unit_id');
        } else {
            $query->where('organization_unit_id', $organizationUnitId);
        }

        if ($periodValue === null || trim($periodValue) === '') {
            $query->whereNull('period_value');
        } else {
            $query->where('period_value', trim($periodValue));
        }

        $model = $query->first();

        if (! $model instanceof Model) {
            return null;
        }

        return $this->toRecord($model);
    }

    public function findByScopeForUpdate(
        int $tenantId,
        ?int $organizationUnitId,
        string $documentType,
        ?string $periodValue,
    ): ?DataRecord {
        $query = $this->query()
            ->where('tenant_id', $tenantId)
            ->where('document_type', trim($documentType))
            ->lockForUpdate();

        if ($organizationUnitId === null) {
            $query->whereNull('organization_unit_id');
        } else {
            $query->where('organization_unit_id', $organizationUnitId);
        }

        if ($periodValue === null || trim($periodValue) === '') {
            $query->whereNull('period_value');
        } else {
            $query->where('period_value', trim($periodValue));
        }

        $model = $query->first();
        if (! $model instanceof Model) {
            return null;
        }

        return $this->toRecord($model);
    }

    public function pageByFilters(
        ?int $tenantId,
        ?int $organizationUnitId,
        ?string $documentType,
        ?string $periodType,
        ?string $periodValue,
        int $perPage,
        int $page,
    ): PagedResult {
        $query = $this->query();

        if ($tenantId !== null) {
            $query->where('tenant_id', $tenantId);
        }

        if ($organizationUnitId !== null) {
            $query->where('organization_unit_id', $organizationUnitId);
        }

        if ($documentType !== null && trim($documentType) !== '') {
            $query->where('document_type', trim($documentType));
        }

        if ($periodType !== null && trim($periodType) !== '') {
            $query->where('period_type', trim($periodType));
        }

        if ($periodValue !== null && trim($periodValue) !== '') {
            $query->where('period_value', trim($periodValue));
        }

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        $items = [];
        foreach ($paginator->items() as $model) {
            if ($model instanceof Model) {
                $items[] = $this->toRecord($model);
            }
        }

        return new PagedResult($items, $paginator->total(), $paginator->currentPage(), $paginator->perPage());
    }

    public function updateNextNumberWithVersion(
        int|string $id,
        int $expectedRowVersion,
        int $nextNumber,
    ): ?DataRecord {
        $updatedRows = $this->query()
            ->whereKey($id)
            ->where('row_version', $expectedRowVersion)
            ->update([
                'next_number' => $nextNumber,
                'row_version' => $expectedRowVersion + 1,
            ]);

        if ($updatedRows !== 1) {
            return null;
        }

        return $this->findById($id);
    }
}
