<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Services\Directory;

use Modules\Core\Contracts\OrganizationUnitDirectoryInterface;
use Modules\OrganizationUnit\Models\OrganizationUnitModel;
use RuntimeException;

final class OrganizationUnitDirectory implements OrganizationUnitDirectoryInterface
{
    public function assertActive(int $tenantId, array $organizationUnitIds, bool $lockForUpdate = false): void
    {
        $ids = $this->normalizeIds($organizationUnitIds);
        if ($ids === []) {
            return;
        }

        $query = OrganizationUnitModel::query()
            ->where('tenant_id', $tenantId)
            ->whereIn('id', $ids)
            ->where('is_active', true)
            ->whereNull('retired_at')
            ->orderBy('id');
        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        if ($query->count() !== count($ids)) {
            throw new RuntimeException('One or more selected organization units are inactive, retired, or unavailable.');
        }
    }

    public function isActive(int $tenantId, int $organizationUnitId, bool $lockForUpdate = false): bool
    {
        if ($tenantId < 1 || $organizationUnitId < 1) {
            return false;
        }

        $query = OrganizationUnitModel::query()
            ->where('tenant_id', $tenantId)
            ->whereKey($organizationUnitId)
            ->where('is_active', true)
            ->whereNull('retired_at');
        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        return $query->select('id')->first() !== null;
    }

    public function summaries(int $tenantId, array $organizationUnitIds): array
    {
        $ids = $this->normalizeIds($organizationUnitIds);
        if ($ids === []) {
            return [];
        }

        return OrganizationUnitModel::query()
            ->where('tenant_id', $tenantId)
            ->whereIn('id', $ids)
            ->where('is_active', true)
            ->whereNull('retired_at')
            ->orderBy('path')
            ->get(['id', 'code', 'name', 'path'])
            ->mapWithKeys(static fn (OrganizationUnitModel $unit): array => [
                (int) $unit->getKey() => [
                    'id' => (int) $unit->getKey(),
                    'code' => (string) $unit->getAttribute('code'),
                    'name' => (string) $unit->getAttribute('name'),
                    'path' => (string) $unit->getAttribute('path'),
                ],
            ])->all();
    }

    public function activeIdsOrderedByPath(int $tenantId, array $organizationUnitIds): array
    {
        return array_keys($this->summaries($tenantId, $organizationUnitIds));
    }

    public function ownershipSummary(int $tenantId, int $organizationUnitId): ?array
    {
        if ($tenantId < 1 || $organizationUnitId < 1) {
            return null;
        }

        $unit = OrganizationUnitModel::query()
            ->where('tenant_id', $tenantId)
            ->whereKey($organizationUnitId)
            ->first(['id', 'code', 'name', 'path']);
        if (! $unit instanceof OrganizationUnitModel) {
            return null;
        }

        return [
            'id' => (int) $unit->getKey(),
            'code' => (string) $unit->getAttribute('code'),
            'name' => (string) $unit->getAttribute('name'),
            'path' => (string) $unit->getAttribute('path'),
        ];
    }

    /** @param list<int> $ids @return list<int> */
    private function normalizeIds(array $ids): array
    {
        $normalized = [];
        foreach ($ids as $id) {
            if (! is_int($id) && ! ctype_digit((string) $id)) {
                throw new RuntimeException('Organization-unit identifiers must be positive integers.');
            }
            $id = (int) $id;
            if ($id < 1) {
                throw new RuntimeException('Organization-unit identifiers must be positive integers.');
            }
            $normalized[$id] = $id;
        }
        ksort($normalized);

        return array_values($normalized);
    }
}
