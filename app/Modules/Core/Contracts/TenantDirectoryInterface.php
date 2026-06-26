<?php

declare(strict_types=1);

namespace Modules\Core\Contracts;

interface TenantDirectoryInterface
{
    /** @return array{id:int,code:string,name:string,status:string}|null */
    public function summary(int $tenantId): ?array;
}
