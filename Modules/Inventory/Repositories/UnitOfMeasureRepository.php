<?php

declare(strict_types=1);

namespace Modules\Inventory\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Modules\Core\Repositories\BaseRepository;
use Modules\Inventory\Entities\UnitOfMeasure;
use Modules\Inventory\Repositories\Contracts\UnitOfMeasureRepositoryInterface;

/**
 * Unit of Measure Repository Implementation
 *
 * Handles all UoM data access operations.
 */
class UnitOfMeasureRepository extends BaseRepository implements UnitOfMeasureRepositoryInterface
{
    /**
     * UnitOfMeasureRepository constructor.
     */
    public function __construct(UnitOfMeasure $model)
    {
        parent::__construct($model);
    }

    /**
     * {@inheritdoc}
     */
    public function findByCode(string $code): ?UnitOfMeasure
    {
        return $this->model->where('code', $code)->first();
    }

    /**
     * {@inheritdoc}
     */
    public function getByCategory(string $category): Collection
    {
        return $this->model
            ->where('uom_category', $category)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getActiveUoms(): Collection
    {
        return $this->model
            ->where('is_active', true)
            ->orderBy('uom_category')
            ->orderBy('name')
            ->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getBaseUnits(): Collection
    {
        return $this->model
            ->where('is_base_unit', true)
            ->where('is_active', true)
            ->get();
    }
}
