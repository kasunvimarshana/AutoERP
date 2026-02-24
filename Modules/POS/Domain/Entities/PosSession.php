<?php

namespace Modules\POS\Domain\Entities;

use Modules\POS\Domain\Enums\PosSessionStatus;

class PosSession
{
    public function __construct(
        private string $id,
        private string $tenantId,
        private string $terminalId,
        private ?string $cashierId,
        private PosSessionStatus $status,
        private string $openingCash,
        private ?string $closingCash,
        private \DateTimeImmutable $openedAt,
        private ?\DateTimeImmutable $closedAt,
        private string $totalSales,
        private int $orderCount,
    ) {}

    public function getId(): string { return $this->id; }
    public function getTenantId(): string { return $this->tenantId; }
    public function getTerminalId(): string { return $this->terminalId; }
    public function getCashierId(): ?string { return $this->cashierId; }
    public function getStatus(): PosSessionStatus { return $this->status; }
    public function getOpeningCash(): string { return $this->openingCash; }
    public function getClosingCash(): ?string { return $this->closingCash; }
    public function getOpenedAt(): \DateTimeImmutable { return $this->openedAt; }
    public function getClosedAt(): ?\DateTimeImmutable { return $this->closedAt; }
    public function getTotalSales(): string { return $this->totalSales; }
    public function getOrderCount(): int { return $this->orderCount; }
}
