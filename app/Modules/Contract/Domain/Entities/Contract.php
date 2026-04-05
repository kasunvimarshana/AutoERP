<?php
declare(strict_types=1);
namespace Modules\Contract\Domain\Entities;

/**
 * A legally binding agreement with a customer or supplier.
 * type: customer | supplier | employment | nda | other
 * status: draft | active | expired | terminated | renewed
 */
class Contract
{
    public const TYPE_CUSTOMER   = 'customer';
    public const TYPE_SUPPLIER   = 'supplier';
    public const TYPE_EMPLOYMENT = 'employment';
    public const TYPE_NDA        = 'nda';
    public const TYPE_OTHER      = 'other';

    public const STATUS_DRAFT      = 'draft';
    public const STATUS_ACTIVE     = 'active';
    public const STATUS_EXPIRED    = 'expired';
    public const STATUS_TERMINATED = 'terminated';
    public const STATUS_RENEWED    = 'renewed';

    public function __construct(
        private ?int $id,
        private int $tenantId,
        private string $contractNumber,
        private string $type,
        private string $status,
        private string $title,
        private ?string $description,
        private ?int $customerId,
        private ?int $supplierId,
        private ?int $ownerId,
        private float $value,
        private string $currency,
        private \DateTimeInterface $startDate,
        private \DateTimeInterface $endDate,
        private ?string $terms,
        private bool $autoRenew,
        private ?\DateTimeInterface $terminatedAt,
        private ?\DateTimeInterface $createdAt,
        private ?\DateTimeInterface $updatedAt,
    ) {}

    public function getId(): ?int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getContractNumber(): string { return $this->contractNumber; }
    public function getType(): string { return $this->type; }
    public function getStatus(): string { return $this->status; }
    public function getTitle(): string { return $this->title; }
    public function getDescription(): ?string { return $this->description; }
    public function getCustomerId(): ?int { return $this->customerId; }
    public function getSupplierId(): ?int { return $this->supplierId; }
    public function getOwnerId(): ?int { return $this->ownerId; }
    public function getValue(): float { return $this->value; }
    public function getCurrency(): string { return $this->currency; }
    public function getStartDate(): \DateTimeInterface { return $this->startDate; }
    public function getEndDate(): \DateTimeInterface { return $this->endDate; }
    public function getTerms(): ?string { return $this->terms; }
    public function isAutoRenew(): bool { return $this->autoRenew; }
    public function getTerminatedAt(): ?\DateTimeInterface { return $this->terminatedAt; }
    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }

    public function activate(): void
    {
        if ($this->status !== self::STATUS_DRAFT) {
            throw new \DomainException("Only draft contracts can be activated.");
        }
        $this->status = self::STATUS_ACTIVE;
    }

    public function terminate(string $reason = ''): void
    {
        if ($this->status === self::STATUS_TERMINATED || $this->status === self::STATUS_EXPIRED) {
            throw new \DomainException("Contract is already terminated or expired.");
        }
        $this->status       = self::STATUS_TERMINATED;
        $this->terminatedAt = new \DateTimeImmutable();
    }

    public function isActive(): bool { return $this->status === self::STATUS_ACTIVE; }

    public function isExpired(\DateTimeInterface $asOf): bool
    {
        return $this->endDate < $asOf;
    }
}
