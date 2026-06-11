<?php

declare(strict_types=1);

namespace Modules\Sales\DTOs;

final readonly class SalesInvoiceSourceData
{
    /**
     * @param  array<int, string>  $lineQuantities
     */
    public function __construct(
        public string $sourceType,
        public int $sourceId,
        public array $lineQuantities = [],
    ) {}
}
