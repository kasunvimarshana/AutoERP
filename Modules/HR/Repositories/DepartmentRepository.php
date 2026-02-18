<?php

declare(strict_types=1);

namespace Modules\HR\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Modules\Core\Repositories\BaseRepository;
use Modules\HR\Entities\Department;
use Modules\HR\Repositories\Contracts\DepartmentRepositoryInterface;

/**
 * Department Repository Implementation
 *
 * Handles all department data access operations.
 */
class DepartmentRepository extends BaseRepository implements DepartmentRepositoryInterface
{
    /**
     * DepartmentRepository constructor.
     */
    public function __construct(Department $model)
    {
        parent::__construct($model);
    }

    /**
     * {@inheritdoc}
     */
    public function findByCode(string $code): ?Department
    {
        return $this->model->where('code', $code)->first();
    }

    /**
     * {@inheritdoc}
     */
    public function getRootDepartments(): Collection
    {
        return $this->model->whereNull('parent_id')->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getChildren(int $parentId): Collection
    {
        return $this->model->where('parent_id', $parentId)->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getTree(): Collection
    {
        return $this->model->with('children')->whereNull('parent_id')->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getActiveDepartments(): Collection
    {
        return $this->model->where('status', 'active')->get();
    }
}
