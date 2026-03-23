<?php

namespace Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\OrganizationUnit\Domain\RepositoryInterfaces\OrganizationUnitRepositoryInterface;
use Modules\OrganizationUnit\Domain\Entities\OrganizationUnit;
use Modules\OrganizationUnit\Domain\ValueObjects\Name;
use Modules\OrganizationUnit\Domain\ValueObjects\Code;
use Modules\OrganizationUnit\Domain\ValueObjects\Metadata;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models\OrganizationUnitModel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EloquentOrganizationUnitRepository extends EloquentRepository implements OrganizationUnitRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(new OrganizationUnitModel());
    }

    /**
     * {@inheritdoc}
     */
    public function save(OrganizationUnit $unit): OrganizationUnit
    {
        $data = [
            'tenant_id'   => $unit->getTenantId(),
            'name'        => $unit->getName()->value(),
            'code'        => $unit->getCode()->value(),
            'description' => $unit->getDescription(),
            'metadata'    => $unit->getMetadata()->toArray(),
            'parent_id'   => $unit->getParentId(),
            '_lft'        => $unit->getLft(),
            '_rgt'        => $unit->getRgt(),
        ];

        DB::transaction(function () use ($unit, $data) {
            if ($unit->getId()) {
                // Update existing
                $model = $this->update($unit->getId(), $data);
            } else {
                // Insert new – we need to calculate lft/rgt before insertion
                $this->insertNode($unit);
                $model = $this->model->where('id', $unit->getId())->first(); // after insertion
            }
        });

        return $this->toDomainEntity($model);
    }

    /**
     * Insert a new node, calculating the correct lft/rgt values.
     */
    protected function insertNode(OrganizationUnit $unit): void
    {
        $parentId = $unit->getParentId();
        $tenantId = $unit->getTenantId();

        if ($parentId === null) {
            // Insert as root – get the maximum rgt among roots
            $maxRgt = $this->model->where('tenant_id', $tenantId)
                ->whereNull('parent_id')
                ->max('_rgt');
            $lft = ($maxRgt ?? 0) + 1;
            $rgt = $lft + 1;
        } else {
            // Get the parent node
            $parent = $this->model->find($parentId);
            if (!$parent) {
                throw new \RuntimeException('Parent not found');
            }
            // Shift all nodes to the right to make space for the new node
            $right = $parent->_rgt;
            $this->shiftLeftRight($tenantId, $right, 2);
            $lft = $right;
            $rgt = $right + 1;
        }

        $unit->setLftRgt($lft, $rgt);

        $model = $this->model->create([
            'tenant_id'   => $unit->getTenantId(),
            'name'        => $unit->getName()->value(),
            'code'        => $unit->getCode()->value(),
            'description' => $unit->getDescription(),
            'metadata'    => $unit->getMetadata()->toArray(),
            'parent_id'   => $unit->getParentId(),
            '_lft'        => $lft,
            '_rgt'        => $rgt,
        ]);
        $unit = new OrganizationUnit(/* ... */); // re‑hydrate with id
        $this->model = $model; // store for later use
    }

    /**
     * Shift left/right values for all nodes >= a given value.
     */
    protected function shiftLeftRight(int $tenantId, int $from, int $delta): void
    {
        $this->model->where('tenant_id', $tenantId)
            ->where('_lft', '>=', $from)
            ->increment('_lft', $delta);
        $this->model->where('tenant_id', $tenantId)
            ->where('_rgt', '>=', $from)
            ->increment('_rgt', $delta);
    }

    /**
     * {@inheritdoc}
     */
    public function moveNode(int $id, ?int $newParentId): void
    {
        $node = $this->model->find($id);
        if (!$node) {
            throw new \RuntimeException('Node not found');
        }

        $oldParentId = $node->parent_id;
        if ($oldParentId === $newParentId) {
            return;
        }

        DB::transaction(function () use ($node, $newParentId) {
            // Remove node and its descendants from the tree
            $width = $node->_rgt - $node->_lft + 1;
            $this->shiftLeftRight($node->tenant_id, $node->_rgt + 1, -$width);
            // Update the node's parent and prepare to re‑insert
            $node->parent_id = $newParentId;
            $node->save();

            // Re‑insert at the new position
            if ($newParentId === null) {
                $maxRgt = $this->model->where('tenant_id', $node->tenant_id)
                    ->whereNull('parent_id')
                    ->max('_rgt');
                $newLft = ($maxRgt ?? 0) + 1;
            } else {
                $newParent = $this->model->find($newParentId);
                $newLft = $newParent->_rgt;
                $this->shiftLeftRight($node->tenant_id, $newLft, $width);
            }
            $newRgt = $newLft + $width - 1;
            $this->model->where('id', $node->id)->update(['_lft' => $newLft, '_rgt' => $newRgt]);
            // Re‑adjust the descendants
            $diff = $newLft - $node->_lft;
            $this->model->where('tenant_id', $node->tenant_id)
                ->where('_lft', '>=', $node->_lft)
                ->where('_rgt', '<=', $node->_rgt)
                ->update([
                    '_lft' => DB::raw("_lft + $diff"),
                    '_rgt' => DB::raw("_rgt + $diff"),
                ]);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function getTree(int $tenantId, ?int $rootId = null): array
    {
        $query = $this->model->where('tenant_id', $tenantId)->orderBy('_lft');
        if ($rootId) {
            $root = $this->model->find($rootId);
            if ($root) {
                $query = $root->getDescendants();
            } else {
                return [];
            }
        }
        $models = $query->get();
        return $this->buildTree($models);
    }

    /**
     * Build a tree from a flat list sorted by _lft.
     */
    protected function buildTree(Collection $models): array
    {
        $tree = [];
        $stack = [];

        foreach ($models as $model) {
            $node = $this->toDomainEntity($model);
            while (count($stack) > 0 && end($stack)->getRgt() < $node->getLft()) {
                array_pop($stack);
            }
            if (count($stack) === 0) {
                $tree[] = $node;
                $stack[] = $node;
            } else {
                $parent = end($stack);
                $parent->addChild($node);
                $stack[] = $node;
            }
        }
        return $tree;
    }

    /**
     * {@inheritdoc}
     */
    public function getDescendants(int $id): array
    {
        $node = $this->model->find($id);
        if (!$node) {
            return [];
        }
        return $node->getDescendants()->map(fn($m) => $this->toDomainEntity($m))->all();
    }

    /**
     * {@inheritdoc}
     */
    public function getAncestors(int $id): array
    {
        $node = $this->model->find($id);
        if (!$node) {
            return [];
        }
        return $node->getAncestors()->map(fn($m) => $this->toDomainEntity($m))->all();
    }

    // Other methods (find, paginate, delete) from EloquentRepository, with proper mapping to domain entities.
}
