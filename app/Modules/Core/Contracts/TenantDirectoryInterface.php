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

interface TenantDirectoryInterface
{
    /** @return array{id:int,code:string,name:string,status:string}|null */
    public function summary(int $tenantId): ?array;
}
