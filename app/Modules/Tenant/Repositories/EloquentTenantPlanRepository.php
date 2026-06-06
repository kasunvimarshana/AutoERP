<?php

declare(strict_types=1);

namespace Modules\Tenant\Repositories;

use Illuminate\Database\Eloquent\Model;
use Modules\Core\DTOs\DataRecord;
use Modules\Core\DTOs\PagedResult;
use Modules\Core\Repositories\EloquentRepository;
use Modules\Tenant\Models\TenantPlanModel;

final class EloquentTenantPlanRepository extends EloquentRepository implements TenantPlanRepositoryInterface
{
    public function __construct(TenantPlanModel $model)
    {
        parent::__construct($model);
    }

    public function findBySlug(string $slug): ?DataRecord
    {
        $model = $this->query()->where('slug', trim($slug))->first();

        if (! $model instanceof Model) {
            return null;
        }

        return $this->toRecord($model);
    }

    public function pageByFilters(
        ?bool $isActive,
        ?string $billingInterval,
        ?string $search,
        int $perPage,
        int $page,
    ): PagedResult {
        $query = $this->query();

        if ($isActive !== null) {
            $query->where('is_active', $isActive);
        }

        if ($billingInterval !== null && trim($billingInterval) !== '') {
            $query->where('billing_interval', trim($billingInterval));
        }

        if ($search !== null && trim($search) !== '') {
            $searchTerm = trim($search);
            $query->where(function ($nestedQuery) use ($searchTerm): void {
                $nestedQuery->where('name', 'like', '%'.$searchTerm.'%')
                    ->orWhere('slug', 'like', '%'.$searchTerm.'%');
            });
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
}
