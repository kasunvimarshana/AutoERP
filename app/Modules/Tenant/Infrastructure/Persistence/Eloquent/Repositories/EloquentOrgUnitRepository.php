<?php

declare(strict_types=1);

namespace Modules\Tenant\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Collection;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Tenant\Domain\RepositoryInterfaces\OrgUnitRepositoryInterface;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\OrgUnitModel;

final class EloquentOrgUnitRepository extends EloquentRepository implements OrgUnitRepositoryInterface
{
    public function __construct(OrgUnitModel $model)
    {
        parent::__construct($model);
    }

    /**
     * {@inheritdoc}
     */
    public function findByTenant(int $tenantId): Collection
    {
        return $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * {@inheritdoc}
     */
    public function findChildren(int $parentId): Collection
    {
        return $this->model->newQuery()
            ->where('parent_id', $parentId)
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getTree(int $tenantId): array
    {
        $all = $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->orderBy('sort_order')
            ->get()
            ->keyBy('id');

        return $this->buildTree($all, null);
    }

    /**
     * Recursively build a nested tree from a flat keyed collection.
     */
    private function buildTree(\Illuminate\Support\Collection $all, ?int $parentId): array
    {
        $branch = [];

        foreach ($all as $item) {
            if ($item->parent_id === $parentId) {
                $children = $this->buildTree($all, $item->id);
                $node = $item->toArray();
                $node['children'] = $children;
                $branch[] = $node;
            }
        }

        return $branch;
    }
}
