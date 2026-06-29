<?php

declare(strict_types=1);

namespace Modules\Invoice\DTOs;

use Modules\Invoice\Enums\InvoiceLineType;

final readonly class ManualInvoiceLineData
{
    public function __construct(
        public string $description,
        public string $quantity,
        public string $unitPrice,
        public InvoiceLineType $lineType = InvoiceLineType::Manual,
        public ?int $itemId = null,
        public ?int $uomId = null,
        public ?int $taxGroupId = null,
        public string $discountAmount = '0.000000',
        public string $chargeAmount = '0.000000',
    ) {}
}
