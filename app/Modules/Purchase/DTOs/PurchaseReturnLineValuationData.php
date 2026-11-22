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

final readonly class PurchaseReturnLineValuationData
{
    public function __construct(
        public string $sourceQuantity,
        public string $previouslyReturnedQuantity,
        public string $remainingQuantity,
        public string $unitPrice,
        public string $costBasis,
        public string $baseAmount,
        public string $discountAmount,
        public string $taxAmount,
        public string $chargeAmount,
        public string $lineTotal,
    ) {}
}
