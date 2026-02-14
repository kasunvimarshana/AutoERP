<?php

namespace App\Modules\Branch\Services;

use App\Core\Services\BaseService;
use App\Modules\Branch\Repositories\BranchRepository;
use Illuminate\Support\Facades\Log;

class BranchService extends BaseService
{
    /**
     * BranchService constructor
     */
    public function __construct(BranchRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Get branch hierarchy
     */
    public function getHierarchy(): array
    {
        try {
            $branches = $this->repository->all();

            return $this->buildHierarchy($branches);
        } catch (\Exception $e) {
            Log::error('Error fetching branch hierarchy: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Build hierarchical structure
     */
    private function buildHierarchy($branches, $parentId = null): array
    {
        $hierarchy = [];

        foreach ($branches as $branch) {
            if ($branch->parent_id === $parentId) {
                $children = $this->buildHierarchy($branches, $branch->id);

                $node = [
                    'id' => $branch->id,
                    'name' => $branch->name,
                    'code' => $branch->code,
                    'is_active' => $branch->is_active,
                ];

                if (! empty($children)) {
                    $node['children'] = $children;
                }

                $hierarchy[] = $node;
            }
        }

        return $hierarchy;
    }

    /**
     * Get active branches
     */
    public function getActive()
    {
        try {
            return $this->repository->getActive();
        } catch (\Exception $e) {
            Log::error('Error fetching active branches: '.$e->getMessage());
            throw $e;
        }
    }
}
