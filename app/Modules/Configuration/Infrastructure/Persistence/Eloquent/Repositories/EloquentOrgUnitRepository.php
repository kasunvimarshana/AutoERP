<?php
declare(strict_types=1);
namespace Modules\Configuration\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Facades\DB;
use Modules\Configuration\Domain\Entities\OrgUnit;
use Modules\Configuration\Domain\RepositoryInterfaces\OrgUnitRepositoryInterface;
use Modules\Configuration\Infrastructure\Persistence\Eloquent\Models\OrgUnitClosureModel;
use Modules\Configuration\Infrastructure\Persistence\Eloquent\Models\OrgUnitModel;

class EloquentOrgUnitRepository implements OrgUnitRepositoryInterface
{
    public function __construct(
        private readonly OrgUnitModel $model,
        private readonly OrgUnitClosureModel $closureModel,
    ) {}

    private function toEntity(OrgUnitModel $m): OrgUnit
    {
        return new OrgUnit(
            $m->id,
            $m->tenant_id,
            $m->parent_id,
            $m->name,
            $m->code,
            $m->type,
            $m->level,
            (bool) $m->is_active,
            $m->created_at,
            $m->updated_at,
        );
    }

    public function findById(int $id): ?OrgUnit
    {
        $m = $this->model->newQuery()->find($id);
        return $m ? $this->toEntity($m) : null;
    }

    public function findByTenant(int $tenantId): array
    {
        return $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->get()
            ->map(fn($m) => $this->toEntity($m))
            ->all();
    }

    public function getTree(int $tenantId): array
    {
        $items = $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->get()
            ->keyBy('id');

        $tree = [];
        foreach ($items as $item) {
            $item->children_list = [];
        }
        foreach ($items as $item) {
            if ($item->parent_id && isset($items[$item->parent_id])) {
                $items[$item->parent_id]->children_list[] = $item;
            } else {
                $tree[] = $item;
            }
        }
        return array_map(fn($m) => $this->buildTreeNode($m), $tree);
    }

    private function buildTreeNode(OrgUnitModel $m): array
    {
        $node = $this->toEntity($m);
        $arr = [
            'id' => $node->getId(),
            'tenant_id' => $node->getTenantId(),
            'parent_id' => $node->getParentId(),
            'name' => $node->getName(),
            'code' => $node->getCode(),
            'type' => $node->getType(),
            'level' => $node->getLevel(),
            'is_active' => $node->isActive(),
            'children' => array_map(fn($child) => $this->buildTreeNode($child), $m->children_list),
        ];
        return $arr;
    }

    public function getDescendants(int $id): array
    {
        $descendantIds = $this->closureModel->newQuery()
            ->where('ancestor_id', $id)
            ->where('depth', '>', 0)
            ->pluck('descendant_id');
        return $this->model->newQuery()
            ->whereIn('id', $descendantIds)
            ->get()
            ->map(fn($m) => $this->toEntity($m))
            ->all();
    }

    public function getAncestors(int $id): array
    {
        $ancestorIds = $this->closureModel->newQuery()
            ->where('descendant_id', $id)
            ->where('depth', '>', 0)
            ->pluck('ancestor_id');
        return $this->model->newQuery()
            ->whereIn('id', $ancestorIds)
            ->get()
            ->map(fn($m) => $this->toEntity($m))
            ->all();
    }

    public function create(array $data): OrgUnit
    {
        $m = $this->model->newQuery()->create($data);

        // Self reference
        $this->closureModel->newQuery()->create([
            'ancestor_id' => $m->id,
            'descendant_id' => $m->id,
            'depth' => 0,
        ]);

        // Copy parent closures
        if (!empty($data['parent_id'])) {
            $parentClosures = $this->closureModel->newQuery()
                ->where('descendant_id', $data['parent_id'])
                ->get();
            foreach ($parentClosures as $closure) {
                $this->closureModel->newQuery()->create([
                    'ancestor_id' => $closure->ancestor_id,
                    'descendant_id' => $m->id,
                    'depth' => $closure->depth + 1,
                ]);
            }
        }

        return $this->toEntity($m);
    }

    public function update(int $id, array $data): ?OrgUnit
    {
        $m = $this->model->newQuery()->find($id);
        if (!$m) {
            return null;
        }
        $m->update($data);
        return $this->toEntity($m->fresh());
    }

    public function delete(int $id): bool
    {
        // Delete closure records
        $this->closureModel->newQuery()
            ->where('descendant_id', $id)
            ->orWhere('ancestor_id', $id)
            ->delete();

        $m = $this->model->newQuery()->find($id);
        return $m ? (bool) $m->delete() : false;
    }

    public function move(int $id, ?int $newParentId): OrgUnit
    {
        // Delete stale closures (except self-reference)
        $this->closureModel->newQuery()
            ->where('descendant_id', $id)
            ->where('depth', '>', 0)
            ->delete();

        // Also remove all closures where descendants of $id are connected to ancestors of old parent
        $descendantIds = $this->closureModel->newQuery()
            ->where('ancestor_id', $id)
            ->pluck('descendant_id');
        $this->closureModel->newQuery()
            ->whereIn('descendant_id', $descendantIds)
            ->where('depth', '>', 0)
            ->whereNotIn('ancestor_id', $descendantIds)
            ->delete();

        if ($newParentId) {
            // Re-insert closures from new parent
            $parentClosures = $this->closureModel->newQuery()
                ->where('descendant_id', $newParentId)
                ->get();
            foreach ($parentClosures as $closure) {
                foreach ($descendantIds as $descId) {
                    $depth = $this->closureModel->newQuery()
                        ->where('ancestor_id', $id)
                        ->where('descendant_id', $descId)
                        ->value('depth') ?? 0;
                    $this->closureModel->newQuery()->firstOrCreate([
                        'ancestor_id' => $closure->ancestor_id,
                        'descendant_id' => $descId,
                        'depth' => $closure->depth + 1 + $depth,
                    ]);
                }
            }
        }

        $m = $this->model->newQuery()->find($id);
        $m->update(['parent_id' => $newParentId]);
        return $this->toEntity($m->fresh());
    }
}
