<?php

declare(strict_types=1);

namespace Modules\Audit\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Audit\DTOs\AuditLogQueryData;
use Modules\Audit\Models\AuditLogModel;
use Modules\Core\DTOs\DataRecord;
use Modules\Core\DTOs\PagedResult;
use Modules\Core\Repositories\EloquentRepository;

final class EloquentAuditLogRepository extends EloquentRepository implements AuditLogRepositoryInterface
{
    public function __construct(AuditLogModel $model)
    {
        parent::__construct($model);
    }

    public function append(array $attributes): DataRecord
    {
        return $this->create($attributes);
    }

    public function pageByQuery(AuditLogQueryData $query): PagedResult
    {
        $builder = $this->applyQueryFilters($this->query(), $query);
        $paginator = $builder->paginate($query->perPage, ['*'], 'page', $query->page);

        $items = [];
        foreach ($paginator->items() as $model) {
            if ($model instanceof Model) {
                $items[] = $this->toRecord($model);
            }
        }

        return new PagedResult(
            $items,
            $paginator->total(),
            $paginator->currentPage(),
            $paginator->perPage(),
        );
    }

    private function applyQueryFilters(Builder $query, AuditLogQueryData $filters): Builder
    {
        if ($filters->tenantId !== null) {
            $query->where('tenant_id', $filters->tenantId);
        }

        if ($filters->organizationUnitId !== null) {
            $query->where('organization_unit_id', $filters->organizationUnitId);
        }

        if ($filters->userId !== null) {
            $query->where('user_id', $filters->userId);
        }

        if ($filters->event !== null && trim($filters->event) !== '') {
            $query->where('event', trim($filters->event));
        }

        if ($filters->auditableType !== null && trim($filters->auditableType) !== '') {
            $query->where('auditable_type', trim($filters->auditableType));
        }

        if ($filters->auditableId !== null && trim($filters->auditableId) !== '') {
            $query->where('auditable_id', trim($filters->auditableId));
        }

        if ($filters->fromDate !== null && trim($filters->fromDate) !== '') {
            $query->where('occurred_at', '>=', trim($filters->fromDate));
        }

        if ($filters->toDate !== null && trim($filters->toDate) !== '') {
            $query->where('occurred_at', '<=', trim($filters->toDate));
        }

        return $query->orderByDesc('occurred_at')->orderByDesc('id');
    }
}
