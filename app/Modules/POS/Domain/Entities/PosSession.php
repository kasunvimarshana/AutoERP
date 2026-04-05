<?php
declare(strict_types=1);
namespace Modules\POS\Domain\Entities;

/**
 * A cashier shift/session at a POS terminal.
 * status: open | closed
 */
class PosSession
{
    public const STATUS_OPEN   = 'open';
    public const STATUS_CLOSED = 'closed';

    public function __construct(
        private ?int $id,
        private int $tenantId,
        private int $terminalId,
        private int $cashierId,
        private string $status,
        private float $openingBalance,
        private ?float $closingBalance,
        private ?string $notes,
        private \DateTimeInterface $openedAt,
        private ?\DateTimeInterface $closedAt,
        private ?\DateTimeInterface $createdAt,
        private ?\DateTimeInterface $updatedAt,
    ) {}

    public function getId(): ?int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getTerminalId(): int { return $this->terminalId; }
    public function getCashierId(): int { return $this->cashierId; }
    public function getStatus(): string { return $this->status; }
    public function getOpeningBalance(): float { return $this->openingBalance; }
    public function getClosingBalance(): ?float { return $this->closingBalance; }
    public function getNotes(): ?string { return $this->notes; }
    public function getOpenedAt(): \DateTimeInterface { return $this->openedAt; }
    public function getClosedAt(): ?\DateTimeInterface { return $this->closedAt; }
    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }

    public function isOpen(): bool { return $this->status === self::STATUS_OPEN; }

    public function close(float $closingBalance, ?string $notes): void
    {
        if ($this->status !== self::STATUS_OPEN) {
            throw new \DomainException("Only open sessions can be closed.");
        }
        $this->status = self::STATUS_CLOSED;
        $this->closingBalance = $closingBalance;
        $this->notes = $notes;
        $this->closedAt = new \DateTimeImmutable();
    }
}
