<?php

declare(strict_types=1);

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
