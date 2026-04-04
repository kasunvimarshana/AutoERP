<?php
namespace Modules\Configuration\Application\Services;

use Modules\Configuration\Application\Contracts\OrgUnitTreeServiceInterface;
use Modules\Configuration\Infrastructure\Persistence\Eloquent\Models\OrgUnitClosureModel;
use Illuminate\Support\Facades\DB;

class OrgUnitTreeService implements OrgUnitTreeServiceInterface
{
    public function __construct(
        private readonly OrgUnitClosureModel $closureModel
    ) {}

    public function insertNode(int $nodeId, ?int $parentId): void
    {
        DB::transaction(function () use ($nodeId, $parentId) {
            $this->closureModel->create(['ancestor_id' => $nodeId, 'descendant_id' => $nodeId, 'depth' => 0]);
            if ($parentId !== null) {
                $rows = $this->closureModel->where('descendant_id', $parentId)->get(['ancestor_id', 'depth']);
                foreach ($rows as $row) {
                    $this->closureModel->create([
                        'ancestor_id'   => $row->ancestor_id,
                        'descendant_id' => $nodeId,
                        'depth'         => $row->depth + 1,
                    ]);
                }
            }
        });
    }

    public function deleteNode(int $nodeId): void
    {
        DB::transaction(function () use ($nodeId) {
            $descendants = $this->closureModel->where('ancestor_id', $nodeId)->pluck('descendant_id');
            $this->closureModel->whereIn('descendant_id', $descendants)->delete();
        });
    }

    public function getDescendants(int $ancestorId, bool $includeSelf = false): array
    {
        $q = $this->closureModel->where('ancestor_id', $ancestorId);
        if (!$includeSelf) {
            $q->where('depth', '>', 0);
        }
        return $q->pluck('descendant_id')->toArray();
    }

    public function getAncestors(int $descendantId, bool $includeSelf = false): array
    {
        $q = $this->closureModel->where('descendant_id', $descendantId);
        if (!$includeSelf) {
            $q->where('depth', '>', 0);
        }
        return $q->orderBy('depth', 'desc')->pluck('ancestor_id')->toArray();
    }

    public function getChildren(int $parentId): array
    {
        return $this->closureModel->where('ancestor_id', $parentId)->where('depth', 1)->pluck('descendant_id')->toArray();
    }

    public function buildTree(int $tenantId): array
    {
        $nodes = DB::table('organization_units')
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->get(['id', 'parent_id', 'name', 'code', 'type', 'is_active']);

        $map = [];
        foreach ($nodes as $n) {
            $map[$n->id] = [
                'id'        => $n->id,
                'name'      => $n->name,
                'code'      => $n->code,
                'type'      => $n->type,
                'parent_id' => $n->parent_id,
                'is_active' => $n->is_active,
                'children'  => [],
            ];
        }
        $roots = [];
        foreach ($map as $id => &$node) {
            if ($node['parent_id'] === null) {
                $roots[] = &$map[$id];
            } elseif (isset($map[$node['parent_id']])) {
                $map[$node['parent_id']]['children'][] = &$map[$id];
            }
        }
        return $roots;
    }
}
