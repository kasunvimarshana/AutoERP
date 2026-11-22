<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\ReferenceData\Contracts;

interface CurrencyDirectoryInterface
{
    /** @return array{id:int,code:string,name:string,symbol:?string,is_active:bool}|null */
    public function find(?int $currencyId, bool $lockForUpdate = false): ?array;

    public function isActive(?int $currencyId, bool $lockForUpdate = false): bool;
}
