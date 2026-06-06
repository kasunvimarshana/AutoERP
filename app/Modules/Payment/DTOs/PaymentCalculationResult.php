<?php

declare(strict_types=1);

namespace Modules\Payment\DTOs;

final readonly class PaymentCalculationResult
{
    /**
     * @param  list<string>  $lineAmounts
     */
    public function __construct(
        public string $totalAmount,
        public string $allocatedAmount,
        public string $unappliedAmount,
        public string $refundedAmount,
        public array $lineAmounts = [],
    ) {}
}
