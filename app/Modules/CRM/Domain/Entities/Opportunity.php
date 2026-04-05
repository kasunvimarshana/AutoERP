<?php
declare(strict_types=1);
namespace Modules\CRM\Domain\Entities;

/**
 * A sales opportunity — a qualified lead actively being worked.
 * stage: prospecting | qualification | proposal | negotiation | closed_won | closed_lost
 * Probability 0-100.
 */
class Opportunity
{
    public const STAGE_PROSPECTING   = 'prospecting';
    public const STAGE_QUALIFICATION = 'qualification';
    public const STAGE_PROPOSAL      = 'proposal';
    public const STAGE_NEGOTIATION   = 'negotiation';
    public const STAGE_CLOSED_WON    = 'closed_won';
    public const STAGE_CLOSED_LOST   = 'closed_lost';

    public function __construct(
        private ?int $id,
        private int $tenantId,
        private string $name,
        private ?int $contactId,
        private ?int $customerId,
        private ?int $ownerId,
        private string $stage,
        private float $probability,      // 0-100
        private float $amount,
        private string $currency,
        private ?\DateTimeInterface $expectedCloseDate,
        private ?string $description,
        private ?\DateTimeInterface $closedAt,
        private ?\DateTimeInterface $createdAt,
        private ?\DateTimeInterface $updatedAt,
    ) {}

    public function getId(): ?int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getName(): string { return $this->name; }
    public function getContactId(): ?int { return $this->contactId; }
    public function getCustomerId(): ?int { return $this->customerId; }
    public function getOwnerId(): ?int { return $this->ownerId; }
    public function getStage(): string { return $this->stage; }
    public function getProbability(): float { return $this->probability; }
    public function getAmount(): float { return $this->amount; }
    public function getCurrency(): string { return $this->currency; }
    public function getExpectedCloseDate(): ?\DateTimeInterface { return $this->expectedCloseDate; }
    public function getDescription(): ?string { return $this->description; }
    public function getClosedAt(): ?\DateTimeInterface { return $this->closedAt; }
    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }

    public function getWeightedAmount(): float
    {
        return $this->amount * ($this->probability / 100);
    }

    public function advanceTo(string $stage): void
    {
        $this->stage = $stage;
    }

    public function closeWon(): void
    {
        $this->stage    = self::STAGE_CLOSED_WON;
        $this->probability = 100.0;
        $this->closedAt = new \DateTimeImmutable();
    }

    public function closeLost(?string $reason = null): void
    {
        $this->stage       = self::STAGE_CLOSED_LOST;
        $this->probability = 0.0;
        $this->closedAt    = new \DateTimeImmutable();
        if ($reason !== null) {
            $this->description = $reason;
        }
    }

    public function isClosed(): bool
    {
        return in_array($this->stage, [self::STAGE_CLOSED_WON, self::STAGE_CLOSED_LOST], true);
    }
}
