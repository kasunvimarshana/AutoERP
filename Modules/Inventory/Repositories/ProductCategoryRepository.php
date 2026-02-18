<?php

declare(strict_types=1);

namespace Modules\Inventory\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Modules\Core\Repositories\BaseRepository;
use Modules\Inventory\Entities\ProductCategory;
use Modules\Inventory\Repositories\Contracts\ProductCategoryRepositoryInterface;

/**
 * Product Category Repository Implementation
 *
 * Handles all product category data access operations.
 */
class ProductCategoryRepository extends BaseRepository implements ProductCategoryRepositoryInterface
{
    /**
     * ProductCategoryRepository constructor.
     */
    public function __construct(ProductCategory $model)
    {
        parent::__construct($model);
    }

    /**
     * {@inheritdoc}
     */
    public function findByCode(string $code): ?ProductCategory
    {
        return $this->model->where('code', $code)->first();
    }

    /**
     * {@inheritdoc}
     */
    public function getRootCategories(): Collection
    {
        return $this->model
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getByParent(int $parentId): Collection
    {
        return $this->model
            ->where('parent_id', $parentId)
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getActiveCategories(): Collection
    {
        return $this->model
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getCategoryTree(): Collection
    {
        return $this->model
            ->with(['children' => function ($query) {
                $query->orderBy('sort_order');
            }])
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->get();
    }
}
