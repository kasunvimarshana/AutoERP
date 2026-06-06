<?php

declare(strict_types=1);

namespace Modules\Invoice\DTOs;

use Modules\Invoice\Enums\InvoiceLineType;

final readonly class InvoiceLineData
{
    /**
     * @param  array<string, mixed>|null  $metadata
     */
    public function __construct(
        public int $lineNumber,
        public string $description,
        public string $quantity,
        public string $unitPrice,
        public InvoiceLineType $lineType = InvoiceLineType::Item,
        public ?int $itemId = null,
        public ?int $uomId = null,
        public string $discountAmount = '0.000000',
        public string $taxAmount = '0.000000',
        public string $chargeAmount = '0.000000',
        public ?string $lineTotal = null,
        public ?string $sourceLineType = null,
        public ?int $sourceLineId = null,
        public ?array $metadata = null,
    ) {}
}
