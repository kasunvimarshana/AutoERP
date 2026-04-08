<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Collection;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Product\Domain\RepositoryInterfaces\CategoryRepositoryInterface;
use Modules\Product\Infrastructure\Persistence\Eloquent\Models\CategoryModel;

class EloquentCategoryRepository extends EloquentRepository implements CategoryRepositoryInterface
{
    public function __construct(CategoryModel $model)
    {
        parent::__construct($model);
    }

    public function findBySlug(string $slug, int $tenantId): mixed
    {
        return $this->model->where('slug', $slug)->where('tenant_id', $tenantId)->first();
    }

    public function getTree(int $tenantId): array
    {
        $roots = $this->model
            ->where('tenant_id', $tenantId)
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->with('children')
            ->get();

        return $this->buildTree($roots);
    }

    public function findByTenant(int $tenantId): Collection
    {
        return $this->model->where('tenant_id', $tenantId)->get();
    }

    private function buildTree($nodes): array
    {
        return $nodes->map(function ($node) {
            $item             = $node->toArray();
            $item['children'] = $node->children->isNotEmpty()
                ? $this->buildTree($node->children)
                : [];
            return $item;
        })->values()->toArray();
    }
}
