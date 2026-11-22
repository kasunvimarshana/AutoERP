<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Inventory\Services;

final class BatchNumberService
{
    private const PREFIX = 'BAT';

    public function __construct(private readonly InventoryNumberService $numbers) {}

    public function next(int $tenantId): string
    {
        return $this->numbers->next($tenantId, self::PREFIX);
    }
}
