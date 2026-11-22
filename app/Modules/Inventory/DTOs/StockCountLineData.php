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

final readonly class StockCountLineData
{
    public function __construct(
        public int $itemId,
        public string $countedQuantity,
        public ?string $systemQuantity = null,
        public ?string $unitCost = null,
        public ?int $itemVariantId = null,
        public ?int $batchId = null,
        public ?int $serialNumberId = null,
        public ?int $uomId = null,
        public ?string $notes = null,
    ) {}
}
