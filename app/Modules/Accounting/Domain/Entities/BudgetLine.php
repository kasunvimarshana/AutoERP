<?php

declare(strict_types=1);

namespace Modules\Accounting\Domain\Entities;

use DateTimeInterface;

class BudgetLine
{
    private const MONTH_KEYS = ['jan','feb','mar','apr','may','jun','jul','aug','sep','oct','nov','dec'];

    public function __construct(
        public readonly string $id,
        public readonly string $tenantId,
        public readonly string $budgetId,
        public readonly string $accountId,
        public readonly string $period,
        public readonly array $amounts,
        public readonly float $totalAmount,
        public readonly ?string $notes,
        public readonly DateTimeInterface $createdAt,
        public readonly DateTimeInterface $updatedAt,
    ) {}

    public function getAmountForMonth(int $month): float
    {
        $key = self::MONTH_KEYS[$month - 1] ?? null;
        return $key !== null ? (float) ($this->amounts[$key] ?? 0.0) : 0.0;
    }

    public function getTotal(): float
    {
        return (float) array_sum($this->amounts);
    }
}
