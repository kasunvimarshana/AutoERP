<?php

namespace Modules\Inventory\Domain\Entities;

/**
 * Immutable domain entity representing an inventory lot (batch or serial number).
 *
 * Lot-based tracking groups multiple units under one lot number (e.g. a production batch).
 * Serial tracking assigns a unique lot number per individual unit (quantity always 1).
 */
readonly class InventoryLot
{
    public function __construct(
        public string  $id,
        public string  $tenant_id,
        public string  $product_id,
        public string  $lot_number,
        public string  $tracking_type,
        public string  $qty,
        public string  $status,
        public ?string $manufacture_date,
        public ?string $expiry_date,
        public ?string $notes,
    ) {}
}
