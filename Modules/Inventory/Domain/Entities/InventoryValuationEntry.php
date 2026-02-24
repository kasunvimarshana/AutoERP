<?php

namespace Modules\Inventory\Domain\Entities;

/**
 * Immutable domain entity representing a single stock valuation ledger entry.
 *
 * Each entry records the unit cost and total value impact of a stock movement.
 * Positive total_value = stock in (receipt/adjustment+); negative = stock out (deduction/adjustment-).
 * The running_balance_qty / running_balance_value fields capture the cumulative position
 * for the product at the time this entry was recorded.
 */
readonly class InventoryValuationEntry
{
    public function __construct(
        public string  $id,
        public string  $tenant_id,
        public string  $product_id,
        public string  $movement_type,
        public string  $qty,
        public string  $unit_cost,
        public string  $total_value,
        public string  $running_balance_qty,
        public string  $running_balance_value,
        public string  $valuation_method,
        public ?string $reference_type,
        public ?string $reference_id,
    ) {}
}
