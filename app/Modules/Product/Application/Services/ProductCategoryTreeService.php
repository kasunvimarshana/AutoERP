<?php
namespace Modules\Product\Application\Services;

use Modules\Product\Application\Contracts\ProductCategoryTreeServiceInterface;
use Modules\Product\Infrastructure\Persistence\Eloquent\Models\ProductCategoryClosureModel;
use Illuminate\Support\Facades\DB;

class ProductCategoryTreeService implements ProductCategoryTreeServiceInterface
{
    public function __construct(
        private readonly ProductCategoryClosureModel $closureModel
    ) {}

    /**
     * Insert closure rows when a new category node is created.
     * Call this after creating a category record.
     */
    public function insertNode(int $nodeId, ?int $parentId): void
    {
        DB::transaction(function () use ($nodeId, $parentId) {
            // Self-reference (depth=0)
            $this->closureModel->create([
                'ancestor_id'   => $nodeId,
                'descendant_id' => $nodeId,
                'depth'         => 0,
            ]);

            // Inherit all ancestors of parent
            if ($parentId !== null) {
                $parentAncestors = $this->closureModel
                    ->where('descendant_id', $parentId)
                    ->get(['ancestor_id', 'depth']);

                foreach ($parentAncestors as $row) {
                    $this->closureModel->create([
                        'ancestor_id'   => $row->ancestor_id,
                        'descendant_id' => $nodeId,
                        'depth'         => $row->depth + 1,
                    ]);
                }
            }
        });
    }

    /**
     * Delete all closure rows where descendant = nodeId
     * (removes the node and its subtree from all ancestor chains).
     */
    public function deleteNode(int $nodeId): void
    {
        DB::transaction(function () use ($nodeId) {
            // Get all descendants of node (including itself)
            $descendants = $this->closureModel
                ->where('ancestor_id', $nodeId)
                ->pluck('descendant_id');

            // Delete all closure rows for each descendant
            $this->closureModel
                ->whereIn('descendant_id', $descendants)
                ->delete();
        });
    }

    /**
     * Get all descendant IDs of a category (not including itself unless depth=0).
     */
    public function getDescendants(int $ancestorId, bool $includeSelf = false): array
    {
        $query = $this->closureModel->where('ancestor_id', $ancestorId);
        if (!$includeSelf) {
            $query->where('depth', '>', 0);
        }
        return $query->pluck('descendant_id')->toArray();
    }

    /**
     * Get all ancestor IDs of a category (not including itself unless depth=0).
     */
    public function getAncestors(int $descendantId, bool $includeSelf = false): array
    {
        $query = $this->closureModel->where('descendant_id', $descendantId);
        if (!$includeSelf) {
            $query->where('depth', '>', 0);
        }
        return $query->orderBy('depth', 'desc')->pluck('ancestor_id')->toArray();
    }

    /**
     * Get immediate children of a category.
     */
    public function getChildren(int $parentId): array
    {
        return $this->closureModel
            ->where('ancestor_id', $parentId)
            ->where('depth', 1)
            ->pluck('descendant_id')
            ->toArray();
    }

    /**
     * Build a nested tree array from the closure table.
     * Returns array of ['id' => ..., 'children' => [...]]
     */
    public function buildTree(int $tenantId): array
    {
        $allNodes = DB::table('product_categories')
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->get(['id', 'parent_id', 'name', 'slug', 'is_active']);

        $nodeMap = [];
        foreach ($allNodes as $node) {
            $nodeMap[$node->id] = [
                'id'        => $node->id,
                'name'      => $node->name,
                'slug'      => $node->slug,
                'parent_id' => $node->parent_id,
                'is_active' => $node->is_active,
                'children'  => [],
            ];
        }

        $roots = [];
        foreach ($nodeMap as $id => &$node) {
            if ($node['parent_id'] === null) {
                $roots[] = &$nodeMap[$id];
            } elseif (isset($nodeMap[$node['parent_id']])) {
                $nodeMap[$node['parent_id']]['children'][] = &$nodeMap[$id];
            }
        }
        return $roots;
    }
}
