<?php

declare(strict_types=1);

namespace Modules\Pos\Domain\Entities;

final class PosPayment
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $tenantId,
        public readonly int $posOrderId,
        public readonly string $method,
        public readonly string $amount,
        public readonly string $currency,
        public readonly ?string $reference,
        public readonly ?string $createdAt,
        public readonly ?string $updatedAt,
    ) {}
}
