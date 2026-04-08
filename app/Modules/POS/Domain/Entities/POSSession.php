<?php

declare(strict_types=1);

namespace Modules\POS\Domain\Entities;

use DateTimeInterface;

class POSSession
{
    public function __construct(
        public readonly string $id,
        public readonly string $tenantId,
        public readonly string $terminalId,
        public readonly string $userId,
        public readonly string $status,
        public readonly DateTimeInterface $openedAt,
        public readonly ?DateTimeInterface $closedAt,
        public readonly float $openingCash,
        public readonly float $closingCash,
        public readonly float $totalSales,
        public readonly float $totalRefunds,
        public readonly ?string $notes,
        public readonly DateTimeInterface $createdAt,
        public readonly DateTimeInterface $updatedAt,
    ) {}

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function close(float $closingCash, float $totalSales, float $totalRefunds, ?string $notes = null): self
    {
        return new self(
            id: $this->id,
            tenantId: $this->tenantId,
            terminalId: $this->terminalId,
            userId: $this->userId,
            status: 'closed',
            openedAt: $this->openedAt,
            closedAt: new \DateTimeImmutable(),
            openingCash: $this->openingCash,
            closingCash: $closingCash,
            totalSales: $totalSales,
            totalRefunds: $totalRefunds,
            notes: $notes ?? $this->notes,
            createdAt: $this->createdAt,
            updatedAt: new \DateTimeImmutable(),
        );
    }
}
