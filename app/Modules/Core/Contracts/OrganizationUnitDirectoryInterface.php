<?php

declare(strict_types=1);

namespace Modules\Core\Contracts;

interface OrganizationUnitDirectoryInterface
{
    /** @param list<int> $organizationUnitIds */
    public function assertActive(int $tenantId, array $organizationUnitIds, bool $lockForUpdate = false): void;

    public function isActive(int $tenantId, int $organizationUnitId, bool $lockForUpdate = false): bool;

    /**
     * @param list<int> $organizationUnitIds
     * @return array<int,array{id:int,code:string,name:string,path:string}>
     */
    public function summaries(int $tenantId, array $organizationUnitIds): array;

    /** @param list<int> $organizationUnitIds @return list<int> */
    public function activeIdsOrderedByPath(int $tenantId, array $organizationUnitIds): array;
}
