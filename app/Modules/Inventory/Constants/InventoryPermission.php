<?php

declare(strict_types=1);

namespace Modules\Inventory\Constants;

final class InventoryPermission
{
    public const STOCK_VIEW = 'inventory.stock.view';
    public const AUDIT_VIEW = 'inventory.audit.view';
    public const RESERVATIONS_VIEW = 'inventory.reservations.view';
    public const RESERVATIONS_MANAGE = 'inventory.reservations.manage';
    public const ALLOCATIONS_VIEW = 'inventory.allocations.view';
    public const ALLOCATIONS_MANAGE = 'inventory.allocations.manage';
    public const ALLOCATIONS_ISSUE = 'inventory.allocations.issue';
    public const ADJUSTMENTS_VIEW = 'inventory.adjustments.view';
    public const ADJUSTMENTS_MANAGE = 'inventory.adjustments.manage';
    public const ADJUSTMENTS_POST = 'inventory.adjustments.post';
    public const TRANSFERS_VIEW = 'inventory.transfers.view';
    public const TRANSFERS_MANAGE = 'inventory.transfers.manage';
    public const TRANSFERS_DISPATCH = 'inventory.transfers.dispatch';
    public const TRANSFERS_RECEIVE = 'inventory.transfers.receive';
    public const VALUATION_VIEW = 'inventory.valuation.view';
    public const COST_ADJUSTMENTS_VIEW = 'inventory.cost_adjustments.view';
    public const COST_ADJUSTMENTS_MANAGE = 'inventory.cost_adjustments.manage';
    public const COST_ADJUSTMENTS_POST = 'inventory.cost_adjustments.post';
    public const STOCK_COUNTS_VIEW = 'inventory.stock_counts.view';
    public const STOCK_COUNTS_MANAGE = 'inventory.stock_counts.manage';
    public const STOCK_COUNTS_APPROVE = 'inventory.stock_counts.approve';
    public const STOCK_COUNTS_POST = 'inventory.stock_counts.post';
    public const TRACKING_VIEW = 'inventory.tracking.view';

    /** @return array<string, string> */
    public static function descriptions(): array
    {
        return [
            self::STOCK_VIEW => 'View inventory stock balances and availability.',
            self::AUDIT_VIEW => 'View inventory state-change audit history.',
            self::RESERVATIONS_VIEW => 'View inventory reservations.',
            self::RESERVATIONS_MANAGE => 'Create and release inventory reservations.',
            self::ALLOCATIONS_VIEW => 'View inventory allocations.',
            self::ALLOCATIONS_MANAGE => 'Create and release inventory allocations.',
            self::ALLOCATIONS_ISSUE => 'Issue allocated inventory.',
            self::ADJUSTMENTS_VIEW => 'View stock adjustments.',
            self::ADJUSTMENTS_MANAGE => 'Create stock adjustments.',
            self::ADJUSTMENTS_POST => 'Post stock adjustments.',
            self::TRANSFERS_VIEW => 'View stock transfers.',
            self::TRANSFERS_MANAGE => 'Create and cancel stock transfers.',
            self::TRANSFERS_DISPATCH => 'Dispatch stock transfers.',
            self::TRANSFERS_RECEIVE => 'Receive stock transfers.',
            self::VALUATION_VIEW => 'View inventory valuation layers.',
            self::COST_ADJUSTMENTS_VIEW => 'View inventory cost adjustments.',
            self::COST_ADJUSTMENTS_MANAGE => 'Create inventory cost adjustments.',
            self::COST_ADJUSTMENTS_POST => 'Post inventory cost adjustments.',
            self::STOCK_COUNTS_VIEW => 'View stock counts.',
            self::STOCK_COUNTS_MANAGE => 'Create stock counts.',
            self::STOCK_COUNTS_APPROVE => 'Approve stock counts.',
            self::STOCK_COUNTS_POST => 'Post stock counts.',
            self::TRACKING_VIEW => 'View inventory batch and serial tracking records.',
        ];
    }

    /** @return list<string> */
    public static function routeAccess(): array
    {
        return array_keys(self::descriptions());
    }
}
