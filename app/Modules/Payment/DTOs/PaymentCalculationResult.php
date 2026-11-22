<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

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
