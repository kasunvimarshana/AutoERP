<?php

declare(strict_types=1);

namespace Modules\Location\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Core\Domain\ValueObjects\Code;
use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\Core\Domain\ValueObjects\Name;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Location\Domain\Entities\Location;
use Modules\Location\Domain\Exceptions\LocationNotFoundException;
use Modules\Location\Domain\RepositoryInterfaces\LocationRepositoryInterface;
use Modules\Location\Infrastructure\Persistence\Eloquent\Models\LocationModel;

class EloquentLocationRepository extends EloquentRepository implements LocationRepositoryInterface
{
    public function __construct(LocationModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (LocationModel $model): Location => $this->mapModelToDomainEntity($model));
    }

    /**
     * {@inheritdoc}
     */
    public function save(Location $location): Location
    {
        $savedModel = null;

        DB::transaction(function () use ($location, &$savedModel) {
            if ($location->getId()) {
                $data = [
                    'tenant_id'   => $location->getTenantId(),
                    'name'        => $location->getName()->value(),
                    'type'        => $location->getType(),
                    'code'        => $location->getCode()?->value(),
                    'description' => $location->getDescription(),
                    'latitude'    => $location->getLatitude(),
                    'longitude'   => $location->getLongitude(),
                    'timezone'    => $location->getTimezone(),
                    'metadata'    => $location->getMetadata()?->toArray(),
                    'parent_id'   => $location->getParentId(),
                ];
                $savedModel = $this->update($location->getId(), $data);
            } else {
                $savedModel = $this->insertNode($location);
            }
        });

        if (! $savedModel instanceof LocationModel) {
            throw new \RuntimeException('Failed to save location.');
        }

        return $this->mapModelToDomainEntity($savedModel);
    }

    /**
     * Insert a new node, calculating the correct lft/rgt values.
     */
    protected function insertNode(Location $location): LocationModel
    {
        $parentId = $location->getParentId();
        $tenantId = $location->getTenantId();

        if ($parentId === null) {
            $maxRgt = $this->model->where('tenant_id', $tenantId)
                ->whereNull('parent_id')
                ->max('_rgt');
            $lft = ($maxRgt ?? 0) + 1;
            $rgt = $lft + 1;
        } else {
            $parent = $this->model->find($parentId);
            if (! $parent) {
                throw new LocationNotFoundException('parent');
            }
            $right = $parent->_rgt;
            $this->shiftLeftRight($tenantId, $right, 2);
            $lft = $right;
            $rgt = $right + 1;
        }

        $location->setLftRgt($lft, $rgt);

        return $this->model->create([
            'tenant_id'   => $location->getTenantId(),
            'name'        => $location->getName()->value(),
            'type'        => $location->getType(),
            'code'        => $location->getCode()?->value(),
            'description' => $location->getDescription(),
            'latitude'    => $location->getLatitude(),
            'longitude'   => $location->getLongitude(),
            'timezone'    => $location->getTimezone(),
            'metadata'    => $location->getMetadata()?->toArray(),
            'parent_id'   => $location->getParentId(),
            '_lft'        => $lft,
            '_rgt'        => $rgt,
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
            throw new LocationNotFoundException;
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
                    throw new LocationNotFoundException('parent');
                }
                $newLft = $newParent->_rgt;
                $this->shiftLeftRight($node->tenant_id, $newLft, $width);
            }
            $newRgt = $newLft + $width - 1;
            $this->model->where('id', $node->id)->update(['_lft' => $newLft, '_rgt' => $newRgt]);

            $diff = (int) ($newLft - $node->_lft);
            if ($diff !== 0) {
                // Use query builder with DB::raw to atomically shift both _lft and _rgt
                // for all descendants. The cast to int ensures no injection risk.
                DB::table($this->model->getTable())
                    ->where('tenant_id', $node->tenant_id)
                    ->where('_lft', '>=', $node->_lft)
                    ->where('_rgt', '<=', $node->_rgt)
                    ->update([
                        '_lft' => DB::raw('_lft + '.(int) $diff),
                        '_rgt' => DB::raw('_rgt + '.(int) $diff),
                    ]);
            }
        });
    }

    /**
     * {@inheritdoc}
     */
    public function getTree(int $tenantId, ?int $rootId = null): array
    {
        if ($rootId) {
            /** @var LocationModel|null $root */
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
        $tree  = [];
        $stack = [];

        foreach ($models as $model) {
            $node = $this->toDomainEntity($model);
            while (count($stack) > 0 && end($stack)->getRgt() < $node->getLft()) {
                array_pop($stack);
            }
            if (count($stack) === 0) {
                $tree[]  = $node;
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
        /** @var LocationModel|null $node */
        $node = $this->model->find($id);
        if (! $node) {
            return [];
        }

        return $node->getDescendants()->map(fn ($m) => $this->mapModelToDomainEntity($m))->all();
    }

    /**
     * {@inheritdoc}
     */
    public function getAncestors(int $id): array
    {
        /** @var LocationModel|null $node */
        $node = $this->model->find($id);
        if (! $node) {
            return [];
        }

        return $node->getAncestors()->map(fn ($m) => $this->mapModelToDomainEntity($m))->all();
    }

    private function mapModelToDomainEntity(LocationModel $model): Location
    {
        return new Location(
            tenantId:    $model->tenant_id,
            name:        new Name($model->name),
            type:        $model->type,
            code:        $model->code !== null ? new Code($model->code) : null,
            description: $model->description,
            latitude:    isset($model->latitude) ? (float) $model->latitude : null,
            longitude:   isset($model->longitude) ? (float) $model->longitude : null,
            timezone:    $model->timezone,
            metadata:    isset($model->metadata) ? new Metadata((array) $model->metadata) : null,
            parentId:    $model->parent_id,
            id:          $model->id,
            lft:         $model->_lft ?? 0,
            rgt:         $model->_rgt ?? 0,
            createdAt:   $model->created_at,
            updatedAt:   $model->updated_at
        );
    }

    public function rebuildTree(): void
    {
        $nodes   = $this->model->orderBy('parent_id')->orderBy('id')->get();
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
