<?php

namespace Modules\Inventory\Domain\Entities;

/**
 * Immutable domain entity representing a physical inventory cycle count.
 *
 * A cycle count captures the expected vs actual stock quantities at a
 * specific warehouse/location on a given date. When posted, the delta
 * is recorded as an adjustment stock movement to reconcile the ledger.
 */
readonly class CycleCount
{
    public function __construct(
        public string  $id,
        public string  $tenant_id,
        public string  $warehouse_id,
        public ?string $location_id,
        public string  $reference,
        public string  $count_date,
        public string  $status,
        public ?string $notes,
    ) {}
}
