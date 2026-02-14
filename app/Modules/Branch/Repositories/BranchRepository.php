<?php

namespace App\Modules\Branch\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Modules\Branch\Models\Branch;
use Illuminate\Database\Eloquent\Collection;

class BranchRepository extends BaseRepository
{
    /**
     * Specify Model class name
     */
    protected function model(): string
    {
        return Branch::class;
    }

    /**
     * Get branches by parent ID
     */
    public function getByParent(?int $parentId = null): Collection
    {
        return $this->model->where('parent_id', $parentId)->get();
    }

    /**
     * Get active branches
     */
    public function getActive(): Collection
    {
        return $this->model->where('is_active', true)->get();
    }

    /**
     * Get branches with children
     */
    public function getWithChildren(): Collection
    {
        return $this->model->with('children')->whereNull('parent_id')->get();
    }
}
