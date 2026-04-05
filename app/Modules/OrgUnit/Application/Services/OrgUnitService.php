<?php
declare(strict_types=1);
namespace Modules\OrgUnit\Application\Services;

use Modules\OrgUnit\Domain\Entities\OrgUnit;
use Modules\OrgUnit\Domain\Exceptions\OrgUnitCircularReferenceException;
use Modules\OrgUnit\Domain\Exceptions\OrgUnitNotFoundException;
use Modules\OrgUnit\Domain\RepositoryInterfaces\OrgUnitRepositoryInterface;

class OrgUnitService
{
    public function __construct(private readonly OrgUnitRepositoryInterface $repository) {}

    // ── Reads ─────────────────────────────────────────────────────────────

    public function findById(int $id): OrgUnit
    {
        $unit = $this->repository->findById($id);
        if ($unit === null) throw new OrgUnitNotFoundException($id);
        return $unit;
    }

    public function findAllByTenant(int $tenantId): array
    {
        return $this->repository->findAllByTenant($tenantId);
    }

    /**
     * Return the flat list of all units arranged as a tree (roots first,
     * each followed by its descendants in depth-first order).
     */
    public function getTree(int $tenantId): array
    {
        $all = $this->repository->findAllByTenant($tenantId);
        usort($all, fn(OrgUnit $a, OrgUnit $b) => strcmp($a->getPath(), $b->getPath()));
        return $all;
    }

    public function getChildren(int $tenantId, int $parentId): array
    {
        return $this->repository->findChildren($tenantId, $parentId);
    }

    public function getDescendants(int $id): array
    {
        $unit = $this->findById($id);
        return $this->repository->findDescendants($unit->getTenantId(), $unit->getPath());
    }

    public function getAncestors(int $id): array
    {
        $unit = $this->findById($id);
        return $this->repository->findAncestors($unit->getTenantId(), $unit->getPath());
    }

    // ── Writes ────────────────────────────────────────────────────────────

    public function create(array $data): OrgUnit
    {
        // Resolve parent metadata
        $parentPath  = '/';
        $parentLevel = -1;

        if (!empty($data['parent_id'])) {
            $parent      = $this->findById((int) $data['parent_id']);
            $parentPath  = $parent->getPath();
            $parentLevel = $parent->getLevel();
        }

        $data['level'] = $parentLevel + 1;
        $data['path']  = '/';   // temporary; will be updated after insert

        $unit = $this->repository->create($data);

        // Now that we have the ID, build the real path and persist it
        $unit->initializePath($parentPath);
        return $this->repository->update($unit->getId(), ['path' => $unit->getPath()]) ?? $unit;
    }

    public function update(int $id, array $data): OrgUnit
    {
        $this->findById($id);
        return $this->repository->update($id, $data) ?? $this->findById($id);
    }

    public function delete(int $id): void
    {
        $this->findById($id);
        $this->repository->delete($id);
    }

    public function activate(int $id): OrgUnit
    {
        $unit = $this->findById($id);
        $unit->activate();
        return $this->repository->update($id, ['is_active' => true]) ?? $unit;
    }

    public function deactivate(int $id): OrgUnit
    {
        $unit = $this->findById($id);
        $unit->deactivate();
        return $this->repository->update($id, ['is_active' => false]) ?? $unit;
    }

    /**
     * Move a unit (and all its descendants) to a new parent.
     * Circular reference detection: the new parent must not be the unit itself
     * or any of its descendants.
     */
    public function move(int $id, ?int $newParentId): OrgUnit
    {
        $unit = $this->findById($id);

        // Circular reference guard
        if ($newParentId !== null) {
            if ($newParentId === $id) {
                throw new OrgUnitCircularReferenceException($id, $newParentId);
            }
            $newParent = $this->findById($newParentId);
            // If the new parent's path contains this unit's id segment, it's a descendant
            if (str_contains($newParent->getPath(), '/' . $id . '/')) {
                throw new OrgUnitCircularReferenceException($id, $newParentId);
            }
        }

        $oldPath     = $unit->getPath();
        $parentPath  = '/';
        $parentLevel = -1;

        if ($newParentId !== null) {
            $newParent   = $this->repository->findById($newParentId);
            $parentPath  = $newParent->getPath();
            $parentLevel = $newParent->getLevel();
        }

        $unit->moveTo($newParentId, $parentPath, $parentLevel);
        $newPath    = $unit->getPath();
        $levelDelta = $unit->getLevel() - ($parentLevel + 1);

        $saved = $this->repository->update($id, [
            'parent_id' => $newParentId,
            'path'      => $newPath,
            'level'     => $unit->getLevel(),
        ]) ?? $unit;

        // Cascade path / level updates to all descendants
        $this->repository->updateDescendantPaths($oldPath, $newPath, $levelDelta);

        return $saved;
    }
}
