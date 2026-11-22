<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Core\Contracts;

interface OrganizationUnitLifecycleBlockerInterface
{
    /** @return list<array{code:string,message:string,count:int}> */
    public function blockers(int $tenantId, int $organizationUnitId): array;
}
