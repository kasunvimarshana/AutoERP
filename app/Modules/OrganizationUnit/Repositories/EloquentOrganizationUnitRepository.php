<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Repositories;

use Illuminate\Database\Eloquent\Model;
use Modules\Core\DTOs\DataRecord;
use Modules\Core\Repositories\EloquentRepository;
use Modules\OrganizationUnit\Models\OrganizationUnitModel;

final class EloquentOrganizationUnitRepository extends EloquentRepository implements OrganizationUnitRepositoryInterface
{
    public function __construct(OrganizationUnitModel $model)
    {
        parent::__construct($model);
    }

    public function listByTenant(int|string $tenantId): array
    {
        $records = [];

        foreach ($this->query()
            ->where('tenant_id', $tenantId)
            ->orderBy('depth')
            ->orderBy('name')
            ->get() as $model) {
            if ($model instanceof Model) {
                $records[] = $this->toRecord($model);
            }
        }

        return $records;
    }

    public function findByTenantAndName(int|string $tenantId, string $name): ?DataRecord
    {
        return $this->firstRecord($this->query()
            ->where('tenant_id', $tenantId)
            ->where('name', trim($name)));
    }

    public function findByTenantAndCode(int|string $tenantId, string $code): ?DataRecord
    {
        return $this->firstRecord($this->query()
            ->where('tenant_id', $tenantId)
            ->where('code', strtoupper(trim($code))));
    }

    public function findByTenantAndPath(int|string $tenantId, string $path): ?DataRecord
    {
        return $this->firstRecord($this->query()
            ->where('tenant_id', $tenantId)
            ->where('path', trim($path)));
    }

    public function findRootByTenant(int|string $tenantId): ?DataRecord
    {
        return $this->firstRecord($this->query()
            ->where('tenant_id', $tenantId)
            ->whereNull('parent_id')
            ->orderBy('id'));
    }

    public function hasChildren(int|string $id, int|string $tenantId): bool
    {
        return $this->query()
            ->where('tenant_id', $tenantId)
            ->where('parent_id', $id)
            ->exists();
    }

    public function moveHierarchy(
        int|string $id,
        int|string $tenantId,
        string $oldPath,
        string $newPath,
        int $newDepth,
    ): DataRecord {
        $oldPath = trim($oldPath);
        if ($oldPath === '') {
            throw new \InvalidArgumentException('Organization unit hierarchy is incomplete.');
        }

        $current = $this->query()
            ->whereKey($id)
            ->where('tenant_id', $tenantId)
            ->firstOrFail();
        $depthDelta = $newDepth - (int) $current->getAttribute('depth');

        if ($oldPath !== $newPath) {
            $descendants = $this->query()
                ->where('tenant_id', $tenantId)
                ->where('path', 'like', $oldPath.'%')
                ->whereKeyNot($id)
                ->orderBy('depth')
                ->get();

            foreach ($descendants as $descendant) {
                if (! $descendant instanceof Model) {
                    continue;
                }

                $path = (string) $descendant->getAttribute('path');
                if (! str_starts_with($path, $oldPath)) {
                    continue;
                }

                $descendant->setAttribute('path', $newPath.substr($path, strlen($oldPath)));
                $descendant->setAttribute(
                    'depth',
                    (int) $descendant->getAttribute('depth') + $depthDelta,
                );
                $descendant->setAttribute(
                    'row_version',
                    (int) $descendant->getAttribute('row_version') + 1,
                );
                $descendant->save();
            }
        }

        $current->setAttribute('path', $newPath);
        $current->setAttribute('depth', $newDepth);
        if ($current->isDirty()) {
            $current->save();
        }

        return $this->toRecord($current->refresh());
    }

    private function firstRecord($query): ?DataRecord
    {
        $model = $query->first();

        return $model instanceof Model ? $this->toRecord($model) : null;
    }

}
