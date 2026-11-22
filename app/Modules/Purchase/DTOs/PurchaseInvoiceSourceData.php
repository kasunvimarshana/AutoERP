<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Purchase\DTOs;

final readonly class PurchaseInvoiceSourceData
{
    /**
     * @param  array<int, string>  $lineQuantities  keyed by source line id
     */
    public function __construct(
        public string $sourceType,
        public int $sourceId,
        public array $lineQuantities = [],
    ) {}
}
