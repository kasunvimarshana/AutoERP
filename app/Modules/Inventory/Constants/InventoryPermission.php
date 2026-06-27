<?php

declare(strict_types=1);

namespace Modules\Inventory\Constants;

final class InventoryPermission
{
    public const VIEW = 'inventory.view';
    public const MANAGE = 'inventory.manage';
    public const POST = 'inventory.post';

    /** @return array<string, string> */
    public static function descriptions(): array
    {
        return [
            self::VIEW => 'View inventory balances, availability, reservations, movements, and valuation.',
            self::MANAGE => 'Create and manage inventory reservations, allocations, transfers, counts, and adjustments.',
            self::POST => 'Approve or post inventory movements, counts, transfers, and cost adjustments.',
        ];
    }
}
