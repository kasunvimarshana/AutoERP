<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Inventory\DTOs;

final readonly class StockTransferLineData
{
    public function __construct(
        public int $itemId,
        public string $quantity,
        public string $unitCost = '0.000000',
        public ?int $itemVariantId = null,
        public ?int $batchId = null,
        public ?int $serialNumberId = null,
        public ?int $uomId = null,
    ) {}
}
