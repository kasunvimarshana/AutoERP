<?php

declare(strict_types=1);

namespace Modules\Procurement\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Modules\Core\Repositories\BaseRepository;
use Modules\Procurement\Entities\Supplier;
use Modules\Procurement\Repositories\Contracts\SupplierRepositoryInterface;

/**
 * Supplier Repository Implementation
 *
 * Handles all supplier data access operations.
 */
class SupplierRepository extends BaseRepository implements SupplierRepositoryInterface
{
    /**
     * SupplierRepository constructor.
     */
    public function __construct(Supplier $model)
    {
        parent::__construct($model);
    }

    /**
     * {@inheritdoc}
     */
    public function findByCode(string $code): ?Supplier
    {
        return $this->model->where('code', $code)->first();
    }

    /**
     * {@inheritdoc}
     */
    public function getSuppliersByStatus(string $status): Collection
    {
        return $this->model->where('status', $status)->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getSuppliersByRating(float $minRating): Collection
    {
        return $this->model->where('rating', '>=', $minRating)->get();
    }

    /**
     * {@inheritdoc}
     */
    public function search(string $query): Collection
    {
        return $this->model
            ->where('name', 'LIKE', "%{$query}%")
            ->orWhere('code', 'LIKE', "%{$query}%")
            ->orWhere('email', 'LIKE', "%{$query}%")
            ->get();
    }
}
