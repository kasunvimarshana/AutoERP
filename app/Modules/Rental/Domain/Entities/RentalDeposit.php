<?php

declare(strict_types=1);

namespace Modules\Rental\Domain\Entities;

class RentalDeposit
{
    public function __construct(
        private readonly int $tenantId,
        private readonly int $rentalBookingId,
        private readonly int $currencyId,
        private readonly float $heldAmount,
        private string $status = 'held',
        private readonly ?int $orgUnitId = null,
        private readonly float $releasedAmount = 0.0,
        private readonly float $forfeitedAmount = 0.0,
        private readonly ?string $heldAt = null,
        private readonly ?string $releasedAt = null,
        private readonly ?int $paymentId = null,
        private readonly ?int $journalEntryId = null,
        private readonly ?array $metadata = null,
        private readonly int $rowVersion = 1,
        private readonly ?int $id = null,
        private readonly ?\DateTimeInterface $createdAt = null,
        private readonly ?\DateTimeInterface $updatedAt = null,
    ) {
        $this->assertStatus($status);
    }

    public function getId(): ?int { return $this->id; }

    public function getTenantId(): int { return $this->tenantId; }

    public function getOrgUnitId(): ?int { return $this->orgUnitId; }

    public function getRentalBookingId(): int { return $this->rentalBookingId; }

    public function getCurrencyId(): int { return $this->currencyId; }

    public function getHeldAmount(): float { return $this->heldAmount; }

    public function getReleasedAmount(): float { return $this->releasedAmount; }

    public function getForfeitedAmount(): float { return $this->forfeitedAmount; }

    public function getStatus(): string { return $this->status; }

    public function getHeldAt(): ?string { return $this->heldAt; }

    public function getReleasedAt(): ?string { return $this->releasedAt; }

    public function getPaymentId(): ?int { return $this->paymentId; }

    public function getJournalEntryId(): ?int { return $this->journalEntryId; }

    public function getMetadata(): ?array { return $this->metadata; }

    public function getRowVersion(): int { return $this->rowVersion; }

    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }

    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }

    private function assertStatus(string $status): void
    {
        if (! in_array($status, ['held', 'released', 'partially_released', 'forfeited'], true)) {
            throw new \InvalidArgumentException("Invalid deposit status: {$status}");
        }
    }
}
