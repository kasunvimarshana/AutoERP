<?php

declare(strict_types=1);

namespace Modules\Order\Domain\Entities;

class ReturnOrder
{
    public function __construct(
        public readonly string $id,
        public readonly int $tenantId,
        public readonly string $returnNumber,
        public readonly string $returnDate,
        public readonly string $type,
        public readonly ?string $sourceOrderId,
        public readonly string $status,
        public readonly float $refundAmount,
        public readonly string $resolution,
    ) {}
}
