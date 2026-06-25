<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Services\Hierarchy;

use Modules\Core\Exceptions\DomainException;
use Modules\OrganizationUnit\Contracts\OrganizationUnitHierarchyReaderInterface;
use Modules\OrganizationUnit\Models\OrganizationUnitModel;

final class OrganizationUnitHierarchyReader implements OrganizationUnitHierarchyReaderInterface
{
    public function __construct(private readonly OrganizationUnitModel $organizationUnits) {}

    public function activeAncestorIds(int $tenantId, int $organizationUnitId): array
    {
        if ($tenantId < 1 || $organizationUnitId < 1) {
            return [];
        }

        $current = $this->organizationUnits->newQuery()
            ->where('tenant_id', $tenantId)
            ->whereKey($organizationUnitId)
            ->whereNull('retired_at')
            ->first(['id', 'path', 'depth']);

        if (! $current instanceof OrganizationUnitModel) {
            return [];
        }

        $path = trim((string) $current->getAttribute('path'));
        $depth = (int) $current->getAttribute('depth');
        if ($path === '' || $depth < 0) {
            throw new DomainException('Organization-unit hierarchy metadata is invalid.');
        }
        if ($depth === 0) {
            return [];
        }

        $prefixes = $this->ancestorPaths($path);
        $ancestors = $this->organizationUnits->newQuery()
            ->where('tenant_id', $tenantId)
            ->whereIn('path', $prefixes)
            ->orderByDesc('depth')
            ->get(['id', 'path', 'depth', 'is_active', 'retired_at'])
            ->keyBy(static fn (OrganizationUnitModel $unit): string => (string) $unit->getAttribute('path'));

        $ids = [];
        foreach (array_reverse($prefixes) as $ancestorPath) {
            $ancestor = $ancestors->get($ancestorPath);
            if (! $ancestor instanceof OrganizationUnitModel) {
                throw new DomainException('Organization-unit hierarchy contains a missing ancestor.');
            }
            if (! (bool) $ancestor->getAttribute('is_active') || $ancestor->getAttribute('retired_at') !== null) {
                break;
            }
            $ids[] = (int) $ancestor->getKey();
        }

        return $ids;
    }

    /** @return list<string> */
    private function ancestorPaths(string $path): array
    {
        $segments = array_values(array_filter(explode('/', trim($path, '/')), static fn (string $segment): bool => $segment !== ''));
        array_pop($segments);

        $paths = [];
        $current = '';
        foreach ($segments as $segment) {
            $current .= '/'.$segment;
            $paths[] = $current;
        }

        return $paths;
    }
}
