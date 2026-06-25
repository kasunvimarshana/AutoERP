<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Services\OrganizationUnits;

use Modules\Core\Exceptions\DomainException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Core\Contracts\ClockInterface;
use Modules\OrganizationUnit\Constants\OrganizationUnitHierarchy;
use Modules\OrganizationUnit\Exceptions\OrganizationUnitException;
use Modules\OrganizationUnit\Models\OrganizationUnitModel;
use Modules\OrganizationUnit\Models\OrganizationUnitTypeModel;
use Modules\OrganizationUnit\Services\Lifecycle\OrganizationUnitLifecycleGuard;

final class OrganizationHierarchyService
{
    public function __construct(
        private readonly OrganizationUnitModel $units,
        private readonly OrganizationUnitTypeModel $types,
        private readonly OrganizationUnitLifecycleGuard $lifecycle,
        private readonly ClockInterface $clock,
    ) {}

    public function createRoot(
        int $tenantId,
        int $typeId,
        string $code,
        string $name,
        ?string $description = null,
    ): OrganizationUnitModel {
        return DB::transaction(function () use ($tenantId, $typeId, $code, $name, $description): OrganizationUnitModel {
            $type = $this->lockAndValidateType($tenantId, $typeId, 0);
            $existing = $this->units->newQuery()
                ->where('tenant_id', $tenantId)
                ->where('root_marker', OrganizationUnitHierarchy::ROOT_MARKER)
                ->lockForUpdate()
                ->first();

            $normalizedName = $this->normalizeName($name);
            $normalizedCode = $this->normalizeRequiredCode($code);
            $path = '/'.$this->segment($normalizedCode, $normalizedName);
            $this->assertPathLength($path);
            $this->assertPathAvailable($tenantId, $path, $existing?->getKey());

            if ($existing instanceof OrganizationUnitModel) {
                if ((string) $existing->getAttribute('code') !== $normalizedCode) {
                    throw OrganizationUnitException::conflict('The protected root organization-unit code is immutable.');
                }
                $oldPath = (string) $existing->getAttribute('path');
                $existing->forceFill([
                    'type_id' => (int) $type->getKey(),
                    'parent_id' => null,
                    'name' => $normalizedName,
                    'code' => $normalizedCode,
                    'path' => $path,
                    'path_hash' => $this->pathHash($path),
                    'depth' => 0,
                    'root_marker' => OrganizationUnitHierarchy::ROOT_MARKER,
                    'is_active' => true,
                    'retired_at' => null,
                    'description' => $description,
                    'row_version' => max(1, (int) $existing->getAttribute('row_version')) + 1,
                ])->save();

                if ($oldPath !== $path) {
                    $this->rebaseDescendants($tenantId, (int) $existing->getKey(), $oldPath, $path, 0);
                }

                return $existing->refresh();
            }

            $root = new OrganizationUnitModel();
            $root->forceFill([
                'tenant_id' => $tenantId,
                'type_id' => (int) $type->getKey(),
                'parent_id' => null,
                'name' => $normalizedName,
                'code' => $normalizedCode,
                'path' => $path,
                'path_hash' => $this->pathHash($path),
                'depth' => 0,
                'root_marker' => OrganizationUnitHierarchy::ROOT_MARKER,
                'is_active' => true,
                'retired_at' => null,
                'description' => $description,
                'row_version' => 1,
            ])->save();

            return $root->refresh();
        }, 3);
    }

    /** @param array<string, mixed> $attributes */
    public function createUnit(int $tenantId, array $attributes): OrganizationUnitModel
    {
        return DB::transaction(function () use ($tenantId, $attributes): OrganizationUnitModel {
            $parentId = $this->positiveInt($attributes['parent_id'] ?? null);
            $typeId = $this->positiveInt($attributes['type_id'] ?? null);
            if ($parentId === null || $typeId === null) {
                throw new DomainException('A parent and organization-unit type are required.');
            }

            $parent = $this->lockUnit($tenantId, $parentId);
            $this->assertOperationalParent($parent);
            $depth = (int) $parent->getAttribute('depth') + 1;
            $type = $this->lockAndValidateType($tenantId, $typeId, $depth);

            $name = $this->normalizeName((string) ($attributes['name'] ?? ''));
            $code = $this->normalizeRequiredCode((string) ($attributes['code'] ?? ''));
            $path = $this->joinPath((string) $parent->getAttribute('path'), $this->segment($code, $name));
            $this->assertPathLength($path);
            $this->assertPathAvailable($tenantId, $path, null);

            $unit = new OrganizationUnitModel();
            $unit->forceFill([
                'tenant_id' => $tenantId,
                'type_id' => (int) $type->getKey(),
                'parent_id' => $parentId,
                'name' => $name,
                'code' => $code,
                'path' => $path,
                'path_hash' => $this->pathHash($path),
                'depth' => $depth,
                'root_marker' => null,
                'is_active' => true,
                'retired_at' => null,
                'description' => $attributes['description'] ?? null,
                'row_version' => 1,
            ])->save();

            return $unit->refresh();
        }, 3);
    }

    /** @param array<string, mixed> $attributes */
    public function updateUnit(int $tenantId, int $unitId, int $expectedVersion, array $attributes): OrganizationUnitModel
    {
        return DB::transaction(function () use ($tenantId, $unitId, $expectedVersion, $attributes): OrganizationUnitModel {
            $unit = $this->lockUnit($tenantId, $unitId);
            $this->assertVersion($unit, $expectedVersion);
            $this->assertNotRetired($unit);

            $isRoot = $unit->getAttribute('root_marker') === OrganizationUnitHierarchy::ROOT_MARKER;
            $parentId = array_key_exists('parent_id', $attributes)
                ? $this->positiveInt($attributes['parent_id'])
                : $this->positiveInt($unit->getAttribute('parent_id'));

            if ($isRoot && $parentId !== null) {
                throw new DomainException('The protected root organization unit cannot be moved.');
            }
            if (! $isRoot && $parentId === null) {
                throw new DomainException('A non-root organization unit must have a parent.');
            }
            if ($parentId === $unitId) {
                throw new DomainException('Organization unit cannot be its own parent.');
            }

            $parent = null;
            if ($parentId !== null) {
                $parent = $this->lockUnit($tenantId, $parentId);
                $this->assertOperationalParent($parent);
                $unitPath = (string) $unit->getAttribute('path');
                $parentPath = (string) $parent->getAttribute('path');
                if ($parentPath === $unitPath || str_starts_with($parentPath, $unitPath.'/')) {
                    throw new DomainException('Organization unit cannot be moved below one of its descendants.');
                }
            }

            $depth = $parent instanceof OrganizationUnitModel ? (int) $parent->getAttribute('depth') + 1 : 0;
            $typeId = $this->positiveInt($attributes['type_id'] ?? $unit->getAttribute('type_id'));
            if ($typeId === null) {
                throw new DomainException('Organization-unit type is required.');
            }
            $this->lockAndValidateType($tenantId, $typeId, $depth);

            $name = $this->normalizeName((string) ($attributes['name'] ?? $unit->getAttribute('name')));
            $code = $this->normalizeRequiredCode((string) ($attributes['code'] ?? $unit->getAttribute('code')));
            $newPath = $parent instanceof OrganizationUnitModel
                ? $this->joinPath((string) $parent->getAttribute('path'), $this->segment($code, $name))
                : '/'.$this->segment($code, $name);
            $this->assertPathLength($newPath);
            $this->assertPathAvailable($tenantId, $newPath, $unitId);

            $oldPath = (string) $unit->getAttribute('path');
            $oldDepth = (int) $unit->getAttribute('depth');
            $unit->forceFill([
                'type_id' => $typeId,
                'parent_id' => $parentId,
                'name' => $name,
                'code' => $code,
                'path' => $newPath,
                'path_hash' => $this->pathHash($newPath),
                'depth' => $depth,
                'description' => array_key_exists('description', $attributes) ? $attributes['description'] : $unit->getAttribute('description'),
                'row_version' => $expectedVersion + 1,
            ])->save();

            if ($oldPath !== $newPath || $oldDepth !== $depth) {
                $this->rebaseDescendants($tenantId, $unitId, $oldPath, $newPath, $depth - $oldDepth);
            }

            return $unit->refresh();
        }, 3);
    }

    public function setActive(int $tenantId, int $unitId, int $expectedVersion, bool $active): OrganizationUnitModel
    {
        return DB::transaction(function () use ($tenantId, $unitId, $expectedVersion, $active): OrganizationUnitModel {
            $unit = $this->lockUnit($tenantId, $unitId);
            $this->assertVersion($unit, $expectedVersion);
            $this->assertNotRetired($unit);

            if ($unit->getAttribute('root_marker') === OrganizationUnitHierarchy::ROOT_MARKER && ! $active) {
                throw OrganizationUnitException::lifecycleBlocked('The protected root organization unit cannot be deactivated.');
            }

            if ($active) {
                $parentId = $this->positiveInt($unit->getAttribute('parent_id'));
                if ($parentId !== null) {
                    $this->assertOperationalParent($this->lockUnit($tenantId, $parentId));
                }
                $this->lockAndValidateType($tenantId, (int) $unit->getAttribute('type_id'), (int) $unit->getAttribute('depth'));
            } else {
                $activeDescendants = $this->units->newQuery()
                    ->where('tenant_id', $tenantId)
                    ->where('path', 'like', $this->escapeLike((string) $unit->getAttribute('path')).'/%')
                    ->where('is_active', true)
                    ->whereNull('retired_at')
                    ->select('id')
                    ->lockForUpdate()
                    ->get()
                    ->count();
                if ($activeDescendants > 0) {
                    throw OrganizationUnitException::lifecycleBlocked('Deactivate all active child organization units first.');
                }
                $this->lifecycle->assertClear($tenantId, $unitId);
            }

            $unit->forceFill([
                'is_active' => $active,
                'row_version' => $expectedVersion + 1,
            ])->save();

            return $unit->refresh();
        }, 3);
    }

    public function retire(int $tenantId, int $unitId, int $expectedVersion): OrganizationUnitModel
    {
        return DB::transaction(function () use ($tenantId, $unitId, $expectedVersion): OrganizationUnitModel {
            $unit = $this->lockUnit($tenantId, $unitId);
            $this->assertVersion($unit, $expectedVersion);
            $this->assertNotRetired($unit);
            if ($unit->getAttribute('root_marker') === OrganizationUnitHierarchy::ROOT_MARKER) {
                throw OrganizationUnitException::lifecycleBlocked('The protected root organization unit cannot be retired.');
            }
            if ((bool) $unit->getAttribute('is_active')) {
                throw OrganizationUnitException::lifecycleBlocked('Deactivate the organization unit before retirement.');
            }

            $childCount = $this->units->newQuery()
                ->where('tenant_id', $tenantId)
                ->where('parent_id', $unitId)
                ->whereNull('retired_at')
                ->select('id')
                ->lockForUpdate()
                ->get()
                ->count();
            if ($childCount > 0) {
                throw OrganizationUnitException::lifecycleBlocked('Retire or move all child organization units first.');
            }

            $this->lifecycle->assertClear($tenantId, $unitId);
            $unit->forceFill([
                'is_active' => false,
                'retired_at' => $this->clock->now(),
                'row_version' => $expectedVersion + 1,
            ])->save();

            return $unit->refresh();
        }, 3);
    }

    /** @param array{object_key:string,mime_type:string,size_bytes:int}|null $logo */
    public function replaceLogo(int $tenantId, int $unitId, int $expectedVersion, ?array $logo): OrganizationUnitModel
    {
        return DB::transaction(function () use ($tenantId, $unitId, $expectedVersion, $logo): OrganizationUnitModel {
            $unit = $this->lockUnit($tenantId, $unitId);
            $this->assertVersion($unit, $expectedVersion);
            $this->assertNotRetired($unit);
            $unit->forceFill([
                'logo_object_key' => $logo['object_key'] ?? null,
                'logo_mime_type' => $logo['mime_type'] ?? null,
                'logo_size_bytes' => $logo['size_bytes'] ?? null,
                'row_version' => $expectedVersion + 1,
            ])->save();

            return $unit->refresh();
        }, 3);
    }

    public function protectedRootId(int $tenantId, bool $lockForUpdate = false): ?int
    {
        $query = $this->units->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('root_marker', OrganizationUnitHierarchy::ROOT_MARKER)
            ->where('is_active', true)
            ->whereNull('retired_at');
        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        $id = $query->value('id');
        return is_numeric($id) ? (int) $id : null;
    }

    public function rootIsReady(int $tenantId, int $organizationUnitId, bool $lockForUpdate = false): bool
    {
        $query = $this->units->newQuery()
            ->where('tenant_id', $tenantId)
            ->whereKey($organizationUnitId)
            ->where('root_marker', OrganizationUnitHierarchy::ROOT_MARKER)
            ->whereNull('parent_id')
            ->where('depth', 0)
            ->where('is_active', true)
            ->whereNull('retired_at');

        if (! $lockForUpdate) {
            return $query->exists();
        }

        return $query->select('id')->lockForUpdate()->first() !== null;
    }

    private function lockUnit(int $tenantId, int $unitId): OrganizationUnitModel
    {
        $unit = $this->units->newQuery()
            ->where('tenant_id', $tenantId)
            ->whereKey($unitId)
            ->lockForUpdate()
            ->first();
        if (! $unit instanceof OrganizationUnitModel) {
            throw OrganizationUnitException::notFound('Organization unit was not found in the active tenant.');
        }

        return $unit;
    }

    private function lockAndValidateType(int $tenantId, int $typeId, int $depth): OrganizationUnitTypeModel
    {
        $type = $this->types->newQuery()
            ->where('tenant_id', $tenantId)
            ->whereKey($typeId)
            ->where('is_active', true)
            ->lockForUpdate()
            ->first();
        if (! $type instanceof OrganizationUnitTypeModel) {
            throw new DomainException('Select an active organization-unit type from the same tenant.');
        }
        if ((int) $type->getAttribute('level') !== $depth) {
            throw new DomainException(sprintf(
                'Organization-unit type [%s] is valid only at hierarchy level %d.',
                (string) $type->getAttribute('name'),
                (int) $type->getAttribute('level'),
            ));
        }

        return $type;
    }

    private function assertOperationalParent(OrganizationUnitModel $parent): void
    {
        if (! (bool) $parent->getAttribute('is_active') || $parent->getAttribute('retired_at') !== null) {
            throw new DomainException('The parent organization unit must be active and not retired.');
        }
    }

    private function assertVersion(OrganizationUnitModel $unit, int $expectedVersion): void
    {
        if ($expectedVersion < 1 || (int) $unit->getAttribute('row_version') !== $expectedVersion) {
            throw OrganizationUnitException::versionConflict('Organization unit changed since it was loaded. Refresh and try again.');
        }
    }

    private function assertNotRetired(OrganizationUnitModel $unit): void
    {
        if ($unit->getAttribute('retired_at') !== null) {
            throw OrganizationUnitException::lifecycleBlocked('A retired organization unit is read-only.');
        }
    }

    private function assertPathAvailable(int $tenantId, string $path, int|string|null $ignoreId): void
    {
        $query = $this->units->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('path_hash', $this->pathHash($path));
        if ($ignoreId !== null) {
            $query->whereKeyNot($ignoreId);
        }
        $existing = $query->lockForUpdate()->first(['id', 'path']);
        if ($existing instanceof OrganizationUnitModel && (string) $existing->getAttribute('path') === $path) {
            throw OrganizationUnitException::conflict('Organization-unit hierarchy path already exists.');
        }
        if ($existing instanceof OrganizationUnitModel) {
            throw OrganizationUnitException::conflict('Organization-unit hierarchy path hash collision detected.');
        }
    }

    private function rebaseDescendants(int $tenantId, int $unitId, string $oldPath, string $newPath, int $depthDelta): void
    {
        /** @var Collection<int, OrganizationUnitModel> $descendants */
        $descendants = $this->units->newQuery()
            ->where('tenant_id', $tenantId)
            ->whereKeyNot($unitId)
            ->where('path', 'like', $this->escapeLike($oldPath).'/%')
            ->orderBy('depth')
            ->lockForUpdate()
            ->get();

        /** @var array<int, int> $newDepthById */
        $newDepthById = [];
        foreach ($descendants as $descendant) {
            $newDepth = (int) $descendant->getAttribute('depth') + $depthDelta;
            if ($newDepth < 1) {
                throw new DomainException('A hierarchy move would place a descendant at an invalid depth.');
            }
            $this->lockAndValidateType(
                $tenantId,
                (int) $descendant->getAttribute('type_id'),
                $newDepth,
            );
            $newDepthById[(int) $descendant->getKey()] = $newDepth;
        }

        foreach ($descendants as $descendant) {
            $currentPath = (string) $descendant->getAttribute('path');
            $rebasedPath = $newPath.substr($currentPath, strlen($oldPath));
            $this->assertPathLength($rebasedPath);
            $this->assertPathAvailable($tenantId, $rebasedPath, $descendant->getKey());
            $descendant->forceFill([
                'path' => $rebasedPath,
                'path_hash' => $this->pathHash($rebasedPath),
                'depth' => $newDepthById[(int) $descendant->getKey()],
                'row_version' => (int) $descendant->getAttribute('row_version') + 1,
            ])->save();
        }
    }

    private function normalizeName(string $name): string
    {
        $name = trim($name);
        if ($name === '') {
            throw new DomainException('Organization-unit name is required.');
        }
        return $name;
    }

    private function normalizeRequiredCode(string $code): string
    {
        $code = Str::upper(trim($code));
        if ($code === '' || preg_match('/^[A-Z0-9][A-Z0-9_-]{0,99}$/D', $code) !== 1) {
            throw new DomainException('Organization-unit code must use letters, numbers, underscores, or hyphens.');
        }
        return $code;
    }

    private function segment(string $code, string $name): string
    {
        $slug = Str::slug($code !== '' ? $code : $name);
        if ($slug === '') {
            throw new DomainException('Organization-unit hierarchy segment could not be derived.');
        }
        return $slug;
    }

    private function joinPath(string $parentPath, string $segment): string
    {
        return rtrim($parentPath, '/').'/'.$segment;
    }

    private function assertPathLength(string $path): void
    {
        $maximum = max(
            OrganizationUnitHierarchy::MINIMUM_PATH_LENGTH,
            (int) config(
                'organization-unit.hierarchy.maximum_path_length',
                OrganizationUnitHierarchy::DEFAULT_MAXIMUM_PATH_LENGTH,
            ),
        );
        if (mb_strlen($path) > $maximum) {
            throw new DomainException('Organization-unit hierarchy path exceeds the configured maximum length.');
        }
    }

    private function pathHash(string $path): string
    {
        return hash('sha256', $path);
    }

    private function escapeLike(string $value): string
    {
        return addcslashes($value, '\\%_');
    }

    private function positiveInt(mixed $value): ?int
    {
        return is_numeric($value) && (int) $value > 0 ? (int) $value : null;
    }
}
