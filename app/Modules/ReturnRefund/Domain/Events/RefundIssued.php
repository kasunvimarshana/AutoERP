<?php

declare(strict_types=1);

namespace Modules\ReturnRefund\Domain\Events;

use Modules\ReturnRefund\Domain\Entities\ReturnRefund;

class RefundIssued
{
    public function __construct(
        public readonly ReturnRefund $returnRefund,
        public readonly string       $refundAmount,
    ) {}
}
