<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Core\Domain\ValueObjects\Code;
use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\Core\Domain\ValueObjects\Name;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\OrganizationUnit\Domain\Entities\OrganizationUnit;
use Modules\OrganizationUnit\Domain\Exceptions\OrganizationUnitNotFoundException;
use Modules\OrganizationUnit\Domain\RepositoryInterfaces\OrganizationUnitRepositoryInterface;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models\OrganizationUnitModel;

class EloquentOrganizationUnitRepository extends EloquentRepository implements OrganizationUnitRepositoryInterface
{
    public function __construct(OrganizationUnitModel $model)
    {
        parent::__construct($model);
    }

    /**
     * {@inheritdoc}
     */
    public function save(OrganizationUnit $unit): OrganizationUnit
    {
        $savedModel = null;

        DB::transaction(function () use ($unit, &$savedModel) {
            if ($unit->getId()) {
                $data = [
                    'tenant_id' => $unit->getTenantId(),
                    'name' => $unit->getName()->value(),
                    'code' => $unit->getCode()?->value(),
                    'description' => $unit->getDescription(),
                    'metadata' => $unit->getMetadata()?->toArray(),
                    'parent_id' => $unit->getParentId(),
                ];
                $savedModel = $this->update($unit->getId(), $data);
            } else {
                $savedModel = $this->insertNode($unit);
            }
        });

        return $this->toDomainEntity($savedModel);
    }

    /**
     * Insert a new node, calculating the correct lft/rgt values.
     */
    protected function insertNode(OrganizationUnit $unit): OrganizationUnitModel
    {
        $parentId = $unit->getParentId();
        $tenantId = $unit->getTenantId();

        if ($parentId === null) {
            $maxRgt = $this->model->where('tenant_id', $tenantId)
                ->whereNull('parent_id')
                ->max('_rgt');
            $lft = ($maxRgt ?? 0) + 1;
            $rgt = $lft + 1;
        } else {
            $parent = $this->model->find($parentId);
            if (! $parent) {
                throw new OrganizationUnitNotFoundException('parent');
            }
            $right = $parent->_rgt;
            $this->shiftLeftRight($tenantId, $right, 2);
            $lft = $right;
            $rgt = $right + 1;
        }

        $unit->setLftRgt($lft, $rgt);

        return $this->model->create([
            'tenant_id' => $unit->getTenantId(),
            'name' => $unit->getName()->value(),
            'code' => $unit->getCode()?->value(),
            'description' => $unit->getDescription(),
            'metadata' => $unit->getMetadata()?->toArray(),
            'parent_id' => $unit->getParentId(),
            '_lft' => $lft,
            '_rgt' => $rgt,
        ]);
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
        if (! $node) {
            throw new OrganizationUnitNotFoundException;
        }

        if ($node->parent_id === $newParentId) {
            return;
        }

        DB::transaction(function () use ($node, $newParentId) {
            $width = $node->_rgt - $node->_lft + 1;
            $this->shiftLeftRight($node->tenant_id, $node->_rgt + 1, -$width);

            $node->parent_id = $newParentId;
            $node->save();

            if ($newParentId === null) {
                $maxRgt = $this->model->where('tenant_id', $node->tenant_id)
                    ->whereNull('parent_id')
                    ->max('_rgt');
                $newLft = ($maxRgt ?? 0) + 1;
            } else {
                $newParent = $this->model->find($newParentId);
                if (! $newParent) {
                    throw new OrganizationUnitNotFoundException('parent');
                }
                $newLft = $newParent->_rgt;
                $this->shiftLeftRight($node->tenant_id, $newLft, $width);
            }
            $newRgt = $newLft + $width - 1;
            $this->model->where('id', $node->id)->update(['_lft' => $newLft, '_rgt' => $newRgt]);

            $diff = (int) ($newLft - $node->_lft);
            if ($diff !== 0) {
                // Use parameterized DB::update() to atomically shift both _lft and _rgt.
                // The table name comes from the Eloquent model (not user input) and is safe.
                DB::update(
                    'UPDATE '.$this->model->getTable().' SET _lft = _lft + ?, _rgt = _rgt + ? WHERE tenant_id = ? AND _lft >= ? AND _rgt <= ?',
                    [$diff, $diff, $node->tenant_id, $node->_lft, $node->_rgt]
                );
            }
        });
    }

    /**
     * {@inheritdoc}
     */
    public function getTree(int $tenantId, ?int $rootId = null): array
    {
        if ($rootId) {
            $root = $this->model->where('tenant_id', $tenantId)->find($rootId);
            if (! $root) {
                return [];
            }
            $models = $root->getDescendants();
            $models->prepend($root);
        } else {
            $models = $this->model->where('tenant_id', $tenantId)->orderBy('_lft')->get();
        }

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
        if (! $node) {
            return [];
        }

        return $node->getDescendants()->map(fn ($m) => $this->toDomainEntity($m))->all();
    }

    /**
     * {@inheritdoc}
     */
    public function getAncestors(int $id): array
    {
        $node = $this->model->find($id);
        if (! $node) {
            return [];
        }

        return $node->getAncestors()->map(fn ($m) => $this->toDomainEntity($m))->all();
    }

    private function toDomainEntity(OrganizationUnitModel $model): OrganizationUnit
    {
        return new OrganizationUnit(
            tenantId: $model->tenant_id,
            name: new Name($model->name),
            code: $model->code !== null ? new Code($model->code) : null,
            description: $model->description,
            metadata: isset($model->metadata) ? new Metadata((array) $model->metadata) : null,
            parentId: $model->parent_id,
            id: $model->id,
            lft: $model->_lft ?? 0,
            rgt: $model->_rgt ?? 0,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at
        );
    }

    public function rebuildTree(): void
    {
        // Rebuilds the nested set left/right values for all nodes.
        // Typically needed after batch imports or corruption recovery.
        $nodes = $this->model->orderBy('parent_id')->orderBy('id')->get();
        $counter = 1;
        $this->rebuildNodeTree($nodes, null, $counter);
    }

    protected function rebuildNodeTree($nodes, ?int $parentId, int &$counter): void
    {
        $children = $nodes->where('parent_id', $parentId);
        foreach ($children as $node) {
            $lft = $counter++;
            $this->rebuildNodeTree($nodes, $node->id, $counter);
            $rgt = $counter++;
            $this->model->where('id', $node->id)->update(['_lft' => $lft, '_rgt' => $rgt]);
        }
    }
}
