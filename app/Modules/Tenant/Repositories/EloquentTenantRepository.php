<?php

declare(strict_types=1);

namespace Modules\Tenant\Repositories;

use Illuminate\Database\Eloquent\Model;
use Modules\Core\DTOs\DataRecord;
use Modules\Core\DTOs\PagedResult;
use Modules\Core\Repositories\EloquentRepository;
use Modules\Tenant\Models\TenantModel;

final class EloquentTenantRepository extends EloquentRepository implements TenantRepositoryInterface
{
    public function __construct(TenantModel $model)
    {
        parent::__construct($model);
    }

    public function findByCode(string $code): ?DataRecord
    {
        $model = $this->query()->where('code', strtoupper(trim($code)))->first();

        if (! $model instanceof Model) {
            return null;
        }

        return $this->toRecord($model);
    }

    public function findByUuid(string $uuid): ?DataRecord
    {
        $model = $this->query()->where('uuid', trim($uuid))->first();

        if (! $model instanceof Model) {
            return null;
        }

        return $this->toRecord($model);
    }

    public function findByIsolationKey(string $isolationKey): ?DataRecord
    {
        $model = $this->query()->where('isolation_key', trim($isolationKey))->first();

        if (! $model instanceof Model) {
            return null;
        }

        return $this->toRecord($model);
    }

    public function pageByFilters(
        ?string $status,
        ?bool $isActive,
        ?string $search,
        int $perPage,
        int $page,
    ): PagedResult {
        $criteria = [];

        if ($status !== null && trim($status) !== '') {
            $criteria['status'] = strtolower(trim($status));
        }

        if ($isActive !== null) {
            $criteria['is_active'] = $isActive;
        }

        if ($search !== null && trim($search) !== '') {
            $searchTerm = trim($search);

            $paginator = $this->query()
                ->where(function ($query) use ($searchTerm): void {
                    $query->where('code', 'like', '%'.$searchTerm.'%')
                        ->orWhere('name', 'like', '%'.$searchTerm.'%')
                        ->orWhere('slug', 'like', '%'.$searchTerm.'%')
                        ->orWhere('configuration_scope', 'like', '%'.$searchTerm.'%');
                })
                ->when(
                    array_key_exists('status', $criteria),
                    fn ($query) => $query->where('status', $criteria['status']),
                )
                ->when(
                    array_key_exists('is_active', $criteria),
                    fn ($query) => $query->where('is_active', $criteria['is_active']),
                )
                ->paginate($perPage, ['*'], 'page', $page);

            $items = [];
            foreach ($paginator->items() as $model) {
                if ($model instanceof Model) {
                    $items[] = $this->toRecord($model);
                }
            }

            return new PagedResult($items, $paginator->total(), $paginator->currentPage(), $paginator->perPage());
        }

        return $this->page($criteria, $perPage, $page);
    }
}
