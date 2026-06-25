<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Contracts;

interface OrganizationUnitLifecycleBlockerInterface
{
    /**
     * @return list<array{code:string,message:string,count:int}>
     */
    public function blockers(int $tenantId, int $organizationUnitId): array;
}
