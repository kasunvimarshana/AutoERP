<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Services\OrganizationUnits;

use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\OrganizationUnit\Constants\OrganizationUnitHierarchy;
use Modules\OrganizationUnit\Models\OrganizationUnitModel;

final class OrganizationHierarchyService
{
    private const MAX_PATH_LENGTH = 1024;

    public function __construct(private readonly OrganizationUnitModel $units) {}

    /** @param array<string, mixed>|null $metadata */
    public function createRoot(
        int $tenantId,
        int $typeId,
        string $code,
        string $name,
        ?string $description = null,
        ?array $metadata = null,
    ): OrganizationUnitModel {
        return DB::transaction(function () use ($tenantId, $typeId, $code, $name, $description, $metadata): OrganizationUnitModel {
            $existing = $this->units->newQuery()
                ->where('tenant_id', $tenantId)
                ->where('root_marker', OrganizationUnitHierarchy::ROOT_MARKER)
                ->lockForUpdate()
                ->first();

            if ($existing instanceof OrganizationUnitModel) {
                $normalizedName = trim($name);
                $normalizedCode = $this->normalizeCode($code);
                $newPath = '/'.$this->normalizeSegment($normalizedCode, $normalizedName);
                $oldPath = (string) $existing->getAttribute('path');

                $this->assertPathAvailable($tenantId, $newPath, (int) $existing->getKey());

                $changes = [
                    'type_id' => $typeId,
                    'name' => $normalizedName,
                    'code' => $normalizedCode,
                    'path' => $newPath,
                    'depth' => 0,
                    'parent_id' => null,
                    'root_marker' => OrganizationUnitHierarchy::ROOT_MARKER,
                    'is_active' => true,
                    'description' => $description,
                    'metadata' => $metadata,
                ];

                $dirty = false;
                foreach ($changes as $attribute => $value) {
                    if ($existing->getAttribute($attribute) !== $value) {
                        $dirty = true;
                        break;
                    }
                }

                if ($dirty) {
                    $existing->forceFill([
                        ...$changes,
                        'row_version' => (int) $existing->getAttribute('row_version') + 1,
                    ])->save();

                    if ($oldPath !== $newPath) {
                        $this->rebaseDescendants($tenantId, (int) $existing->getKey(), $oldPath, $newPath, 0);
                    }
                }

                return $existing->refresh();
            }

            $segment = $this->normalizeSegment($code, $name);
            $path = '/'.$segment;
            $this->assertPathAvailable($tenantId, $path, null);

            $root = new OrganizationUnitModel();
            $root->forceFill([
                'tenant_id' => $tenantId,
                'type_id' => $typeId,
                'parent_id' => null,
                'name' => trim($name),
                'code' => $this->normalizeCode($code),
                'path' => $path,
                'depth' => 0,
                'root_marker' => OrganizationUnitHierarchy::ROOT_MARKER,
                'is_active' => true,
                'description' => $description,
                'metadata' => $metadata,
                'row_version' => 1,
            ])->save();

            return $root->refresh();
        }, 3);
    }

    /** @param array<string, mixed> $attributes */
    public function createUnit(int $tenantId, array $attributes): OrganizationUnitModel
    {
        return DB::transaction(function () use ($tenantId, $attributes): OrganizationUnitModel {
            $parentId = isset($attributes['parent_id']) ? (int) $attributes['parent_id'] : null;
            if ($parentId === null) {
                throw new DomainException('A non-root organization unit must have a parent.');
            }

            $parent = $this->lockUnit($tenantId, $parentId);
            if (! (bool) $parent->getAttribute('is_active')) {
                throw new DomainException('An organization unit cannot be created under an inactive parent.');
            }

            $name = trim((string) ($attributes['name'] ?? ''));
            $code = $this->normalizeNullableCode($attributes['code'] ?? null);
            $segment = $this->normalizeSegment($code, $name);
            $path = $this->joinPath((string) $parent->getAttribute('path'), $segment);
            $this->assertPathAvailable($tenantId, $path, null);

            $unit = new OrganizationUnitModel();
            $unit->forceFill([
                'tenant_id' => $tenantId,
                'type_id' => $attributes['type_id'] ?? null,
                'parent_id' => $parentId,
                'name' => $name,
                'code' => $code,
                'image_path' => $attributes['image_path'] ?? null,
                'path' => $path,
                'depth' => (int) $parent->getAttribute('depth') + 1,
                'root_marker' => null,
                'is_active' => $attributes['is_active'] ?? true,
                'description' => $attributes['description'] ?? null,
                'metadata' => $attributes['metadata'] ?? null,
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
            if ((int) $unit->getAttribute('row_version') !== $expectedVersion) {
                throw new DomainException('Organization unit changed since it was loaded. Refresh and try again.');
            }

            $isRoot = $unit->getAttribute('root_marker') === OrganizationUnitHierarchy::ROOT_MARKER;
            $parentId = array_key_exists('parent_id', $attributes)
                ? ($attributes['parent_id'] === null ? null : (int) $attributes['parent_id'])
                : ($unit->getAttribute('parent_id') === null ? null : (int) $unit->getAttribute('parent_id'));

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
                $currentPath = (string) $unit->getAttribute('path');
                $parentPath = (string) $parent->getAttribute('path');
                if ($parentPath === $currentPath || str_starts_with($parentPath, $currentPath.'/')) {
                    throw new DomainException('Organization unit cannot be moved below one of its descendants.');
                }
                if (! (bool) $parent->getAttribute('is_active')) {
                    throw new DomainException('Organization unit cannot be moved under an inactive parent.');
                }
            }

            $name = trim((string) ($attributes['name'] ?? $unit->getAttribute('name')));
            $code = array_key_exists('code', $attributes)
                ? $this->normalizeNullableCode($attributes['code'])
                : $this->normalizeNullableCode($unit->getAttribute('code'));
            $segment = $this->normalizeSegment($code, $name);
            $newPath = $parent instanceof OrganizationUnitModel
                ? $this->joinPath((string) $parent->getAttribute('path'), $segment)
                : '/'.$segment;
            $newDepth = $parent instanceof OrganizationUnitModel
                ? (int) $parent->getAttribute('depth') + 1
                : 0;
            $oldPath = (string) $unit->getAttribute('path');
            $oldDepth = (int) $unit->getAttribute('depth');

            $this->assertPathAvailable($tenantId, $newPath, $unitId);

            $unit->forceFill([
                'type_id' => $attributes['type_id'] ?? $unit->getAttribute('type_id'),
                'parent_id' => $parentId,
                'name' => $name,
                'code' => $code,
                'image_path' => array_key_exists('image_path', $attributes) ? $attributes['image_path'] : $unit->getAttribute('image_path'),
                'path' => $newPath,
                'depth' => $newDepth,
                'is_active' => array_key_exists('is_active', $attributes) ? (bool) $attributes['is_active'] : (bool) $unit->getAttribute('is_active'),
                'description' => array_key_exists('description', $attributes) ? $attributes['description'] : $unit->getAttribute('description'),
                'metadata' => array_key_exists('metadata', $attributes) ? $attributes['metadata'] : $unit->getAttribute('metadata'),
                'row_version' => $expectedVersion + 1,
            ])->save();

            if ($oldPath !== $newPath || $oldDepth !== $newDepth) {
                $this->rebaseDescendants($tenantId, $unitId, $oldPath, $newPath, $newDepth - $oldDepth);
            }

            return $unit->refresh();
        }, 3);
    }

    public function setActive(int $tenantId, int $unitId, int $expectedVersion, bool $active): OrganizationUnitModel
    {
        return DB::transaction(function () use ($tenantId, $unitId, $expectedVersion, $active): OrganizationUnitModel {
            $unit = $this->lockUnit($tenantId, $unitId);
            if ((int) $unit->getAttribute('row_version') !== $expectedVersion) {
                throw new DomainException('Organization unit changed since it was loaded. Refresh and try again.');
            }
            if (! $active && $unit->getAttribute('root_marker') === OrganizationUnitHierarchy::ROOT_MARKER) {
                throw new DomainException('The protected root organization unit cannot be deactivated.');
            }
            if (! $active && $this->hasActiveDescendants($tenantId, (string) $unit->getAttribute('path'))) {
                throw new DomainException('Deactivate child organization units before deactivating this unit.');
            }

            $unit->forceFill([
                'is_active' => $active,
                'row_version' => $expectedVersion + 1,
            ])->save();

            return $unit->refresh();
        }, 3);
    }

    public function deleteUnit(int $tenantId, int $unitId, int $expectedVersion): void
    {
        DB::transaction(function () use ($tenantId, $unitId, $expectedVersion): void {
            $unit = $this->lockUnit($tenantId, $unitId);
            if ((int) $unit->getAttribute('row_version') !== $expectedVersion) {
                throw new DomainException('Organization unit changed since it was loaded. Refresh and try again.');
            }
            if ($unit->getAttribute('root_marker') === OrganizationUnitHierarchy::ROOT_MARKER) {
                throw new DomainException('The protected root organization unit cannot be deleted.');
            }
            if ($this->units->newQuery()->where('tenant_id', $tenantId)->where('parent_id', $unitId)->exists()) {
                throw new DomainException('Move or delete child organization units before deleting this unit.');
            }

            $unit->delete();
        }, 3);
    }

    public function rootIsReady(int $tenantId, int $organizationUnitId, bool $lockForUpdate = false): bool
    {
        return $this->rootQuery($tenantId, $lockForUpdate)
            ->whereKey($organizationUnitId)
            ->exists();
    }

    public function protectedRootId(int $tenantId, bool $lockForUpdate = false): ?int
    {
        $value = $this->rootQuery($tenantId, $lockForUpdate)->value('id');

        return is_numeric($value) && (int) $value > 0 ? (int) $value : null;
    }

    private function rootQuery(int $tenantId, bool $lockForUpdate): \Illuminate\Database\Eloquent\Builder
    {
        $query = $this->units->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('root_marker', OrganizationUnitHierarchy::ROOT_MARKER)
            ->whereNull('parent_id')
            ->where('depth', 0)
            ->where('is_active', true)
            ->whereNotNull('type_id')
            ->whereNotNull('path')
            ->whereHas('type', static fn ($type) => $type
                ->where('is_active', true)
                ->whereNull('deleted_at'));
        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        return $query;
    }

    private function lockUnit(int $tenantId, int $unitId): OrganizationUnitModel
    {
        $unit = $this->units->newQuery()
            ->where('tenant_id', $tenantId)
            ->whereKey($unitId)
            ->lockForUpdate()
            ->first();

        if (! $unit instanceof OrganizationUnitModel) {
            throw new DomainException('Organization unit was not found in the current tenant.');
        }

        return $unit;
    }

    private function rebaseDescendants(
        int $tenantId,
        int $unitId,
        string $oldPath,
        string $newPath,
        int $depthDelta,
    ): void {
        $descendants = $this->units->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('id', '!=', $unitId)
            ->where('path', 'like', $oldPath.'/%')
            ->orderBy('depth')
            ->lockForUpdate()
            ->get();

        foreach ($descendants as $descendant) {
            if (! $descendant instanceof OrganizationUnitModel) {
                continue;
            }

            $path = (string) $descendant->getAttribute('path');
            $rebasedPath = $newPath.substr($path, strlen($oldPath));
            if (mb_strlen($rebasedPath) > self::MAX_PATH_LENGTH) {
                throw new DomainException('Moving this organization unit would create an excessively long hierarchy path.');
            }

            $descendant->forceFill([
                'path' => $rebasedPath,
                'depth' => (int) $descendant->getAttribute('depth') + $depthDelta,
                'row_version' => (int) $descendant->getAttribute('row_version') + 1,
            ])->save();
        }
    }

    private function hasActiveDescendants(int $tenantId, string $path): bool
    {
        return $this->units->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('path', 'like', $path.'/%')
            ->where('is_active', true)
            ->exists();
    }

    private function assertPathAvailable(int $tenantId, string $path, ?int $exceptId): void
    {
        $query = $this->units->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('path', $path)
            ->lockForUpdate();
        if ($exceptId !== null) {
            $query->where('id', '!=', $exceptId);
        }
        if ($query->exists()) {
            throw new DomainException('An organization unit with the same code or name already exists under this parent.');
        }
        if (mb_strlen($path) > self::MAX_PATH_LENGTH) {
            throw new DomainException('Organization hierarchy path is too long.');
        }
    }

    private function joinPath(string $parentPath, string $segment): string
    {
        return rtrim($parentPath, '/').'/'.$segment;
    }

    private function normalizeSegment(?string $code, string $name): string
    {
        $source = trim((string) $code) !== '' ? trim((string) $code) : trim($name);
        $segment = Str::slug($source);
        if ($segment === '') {
            throw new DomainException('Organization unit code or name must contain at least one path-safe character.');
        }

        return mb_substr($segment, 0, 100);
    }

    private function normalizeCode(string $code): string
    {
        $normalized = $this->normalizeNullableCode($code);
        if ($normalized === null) {
            throw new DomainException('Root organization code is required.');
        }

        return $normalized;
    }

    private function normalizeNullableCode(mixed $code): ?string
    {
        if ($code === null) {
            return null;
        }
        $normalized = strtoupper(trim((string) $code));

        return $normalized === '' ? null : mb_substr($normalized, 0, 100);
    }
}
