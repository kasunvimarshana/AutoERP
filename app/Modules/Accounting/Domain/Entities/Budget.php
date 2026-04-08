<?php

declare(strict_types=1);

namespace Modules\Accounting\Domain\Entities;

use DateTimeInterface;

class Budget
{
    public function __construct(
        public readonly string $id,
        public readonly string $tenantId,
        public readonly string $name,
        public readonly int $fiscalYear,
        public readonly DateTimeInterface $startDate,
        public readonly DateTimeInterface $endDate,
        public readonly string $status,
        public readonly float $totalAmount,
        public readonly ?string $notes,
        public readonly DateTimeInterface $createdAt,
        public readonly DateTimeInterface $updatedAt,
    ) {}

    public function isActive(): bool { return $this->status === 'active'; }

    public function isOverBudget(float $actual): bool
    {
        return $actual - $this->totalAmount > PHP_FLOAT_EPSILON;
    }
}
