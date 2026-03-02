<?php

declare(strict_types=1);

namespace Modules\Pos\Domain\Entities;

final class PosSession
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $tenantId,
        public readonly int $userId,
        public readonly string $reference,
        public readonly string $status,
        public readonly string $openedAt,
        public readonly ?string $closedAt,
        public readonly string $currency,
        public readonly string $openingFloat,
        public readonly string $closingFloat,
        public readonly string $totalSales,
        public readonly string $totalRefunds,
        public readonly ?string $notes,
        public readonly ?string $createdAt,
        public readonly ?string $updatedAt,
    ) {}
}
