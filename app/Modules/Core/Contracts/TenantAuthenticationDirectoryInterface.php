<?php

declare(strict_types=1);

namespace Modules\Core\Contracts;

interface TenantAuthenticationDirectoryInterface
{
    /** @return array{id:int,code:string,name:string,status:string}|null */
    public function findActive(int $tenantId): ?array;

    /** @return list<string> */
    public function enabledModules(int $tenantId): array;
}
