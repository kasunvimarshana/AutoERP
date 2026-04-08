<?php

declare(strict_types=1);

namespace Modules\Configuration\Application\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Modules\Configuration\Application\Contracts\OrgUnitServiceInterface;
use Modules\Configuration\Domain\Entities\OrgUnit;
use Modules\Configuration\Domain\Events\OrgUnitCreated;
use Modules\Configuration\Domain\Events\OrgUnitMoved;
use Modules\Configuration\Domain\RepositoryInterfaces\OrgUnitRepositoryInterface;
use Modules\Core\Domain\Exceptions\NotFoundException;
use RuntimeException;

class OrgUnitService implements OrgUnitServiceInterface
{
    public function __construct(
        private readonly OrgUnitRepositoryInterface $orgUnitRepository,
    ) {}

    public function getOrgUnit(string $tenantId, string $id): OrgUnit
    {
        $unit = $this->orgUnitRepository->findById($tenantId, $id);

        if ($unit === null) {
            throw new NotFoundException("OrgUnit [{$id}] not found.");
        }

        return $unit;
    }

    public function createOrgUnit(string $tenantId, array $data): OrgUnit
    {
        return DB::transaction(function () use ($tenantId, $data): OrgUnit {
            $parentId = $data['parent_id'] ?? null;
            $id = (string) Str::uuid();

            [$path, $level] = $this->computePathAndLevel($tenantId, $parentId, $id);

            $now = now();
            $unit = new OrgUnit(
                id: $id,
                tenantId: $tenantId,
                name: $data['name'],
                type: $data['type'] ?? 'department',
                code: $data['code'],
                parentId: $parentId,
                path: $path,
                level: $level,
                isActive: (bool) ($data['is_active'] ?? true),
                metadata: $data['metadata'] ?? [],
                createdAt: $now,
                updatedAt: $now,
            );

            $this->orgUnitRepository->save($unit);

            Event::dispatch(new OrgUnitCreated($unit));

            return $unit;
        });
    }

    public function updateOrgUnit(string $tenantId, string $id, array $data): OrgUnit
    {
        return DB::transaction(function () use ($tenantId, $id, $data): OrgUnit {
            $existing = $this->getOrgUnit($tenantId, $id);

            $updated = new OrgUnit(
                id: $existing->id,
                tenantId: $existing->tenantId,
                name: $data['name'] ?? $existing->name,
                type: $data['type'] ?? $existing->type,
                code: $data['code'] ?? $existing->code,
                parentId: $existing->parentId,
                path: $existing->path,
                level: $existing->level,
                isActive: (bool) ($data['is_active'] ?? $existing->isActive),
                metadata: $data['metadata'] ?? $existing->metadata,
                createdAt: $existing->createdAt,
                updatedAt: now(),
            );

            $this->orgUnitRepository->save($updated);

            return $updated;
        });
    }

    public function deleteOrgUnit(string $tenantId, string $id): void
    {
        DB::transaction(function () use ($tenantId, $id): void {
            $this->getOrgUnit($tenantId, $id);
            $this->orgUnitRepository->delete($tenantId, $id);
        });
    }

    public function getAllOrgUnits(string $tenantId): array
    {
        return $this->orgUnitRepository->findAll($tenantId);
    }

    public function getTree(string $tenantId): array
    {
        $all = $this->orgUnitRepository->findAll($tenantId);
        $map = [];

        foreach ($all as $unit) {
            $map[$unit->id] = ['unit' => $unit, 'children' => []];
        }

        $roots = [];
        foreach ($map as $id => &$node) {
            $parentId = $node['unit']->parentId;
            if ($parentId !== null && isset($map[$parentId])) {
                $map[$parentId]['children'][] = &$node;
            } else {
                $roots[] = &$node;
            }
        }
        unset($node);

        return $roots;
    }

    public function getDescendants(string $tenantId, string $id): array
    {
        $unit = $this->getOrgUnit($tenantId, $id);

        return $this->orgUnitRepository->findDescendants($tenantId, $unit->path);
    }

    public function getAncestors(string $tenantId, string $id): array
    {
        $unit = $this->getOrgUnit($tenantId, $id);
        $all = $this->orgUnitRepository->findAll($tenantId);

        return array_values(array_filter($all, fn(OrgUnit $u) => $u->isAncestorOf($unit)));
    }

    public function moveOrgUnit(string $tenantId, string $id, ?string $newParentId): OrgUnit
    {
        return DB::transaction(function () use ($tenantId, $id, $newParentId): OrgUnit {
            $unit = $this->getOrgUnit($tenantId, $id);
            $previousParentId = $unit->parentId;

            if ($newParentId !== null) {
                $newParent = $this->getOrgUnit($tenantId, $newParentId);
                if ($newParent->isDescendantOf($unit) || $newParentId === $id) {
                    throw new RuntimeException('Cannot move an org unit to one of its own descendants.');
                }
            }

            [$newPath, $newLevel] = $this->computePathAndLevel($tenantId, $newParentId, $id);

            $descendants = $this->orgUnitRepository->findDescendants($tenantId, $unit->path);
            $oldPathPrefix = $unit->path;

            $moved = new OrgUnit(
                id: $unit->id,
                tenantId: $unit->tenantId,
                name: $unit->name,
                type: $unit->type,
                code: $unit->code,
                parentId: $newParentId,
                path: $newPath,
                level: $newLevel,
                isActive: $unit->isActive,
                metadata: $unit->metadata,
                createdAt: $unit->createdAt,
                updatedAt: now(),
            );
            $this->orgUnitRepository->save($moved);

            foreach ($descendants as $descendant) {
                $updatedPath = $newPath . substr($descendant->path, strlen($oldPathPrefix));
                $updatedLevel = $newLevel + ($descendant->level - $unit->level);
                $updatedDescendant = new OrgUnit(
                    id: $descendant->id,
                    tenantId: $descendant->tenantId,
                    name: $descendant->name,
                    type: $descendant->type,
                    code: $descendant->code,
                    parentId: $descendant->parentId,
                    path: $updatedPath,
                    level: $updatedLevel,
                    isActive: $descendant->isActive,
                    metadata: $descendant->metadata,
                    createdAt: $descendant->createdAt,
                    updatedAt: now(),
                );
                $this->orgUnitRepository->save($updatedDescendant);
            }

            Event::dispatch(new OrgUnitMoved($moved, $previousParentId));

            return $moved;
        });
    }

    /** @return array{0: string, 1: int} */
    private function computePathAndLevel(string $tenantId, ?string $parentId, string $id): array
    {
        if ($parentId === null) {
            return [$id, 0];
        }

        $parent = $this->orgUnitRepository->findById($tenantId, $parentId);
        if ($parent === null) {
            throw new NotFoundException("Parent OrgUnit [{$parentId}] not found.");
        }

        return [$parent->path . '/' . $id, $parent->level + 1];
    }
}
