<?php
declare(strict_types=1);
namespace Modules\CRM\Domain\Entities;

/**
 * A sales lead — an unqualified enquiry that may become an Opportunity.
 * status: new | contacted | qualified | disqualified | converted
 * source: web | phone | email | referral | trade_show | social | other
 */
class Lead
{
    public const STATUS_NEW          = 'new';
    public const STATUS_CONTACTED    = 'contacted';
    public const STATUS_QUALIFIED    = 'qualified';
    public const STATUS_DISQUALIFIED = 'disqualified';
    public const STATUS_CONVERTED    = 'converted';

    public function __construct(
        private ?int $id,
        private int $tenantId,
        private string $name,               // prospect full name or company
        private ?string $email,
        private ?string $phone,
        private ?string $company,
        private string $source,
        private string $status,
        private ?float $estimatedValue,
        private ?int $ownerId,
        private ?string $notes,
        private ?\DateTimeInterface $convertedAt,
        private ?\DateTimeInterface $createdAt,
        private ?\DateTimeInterface $updatedAt,
    ) {}

    public function getId(): ?int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getName(): string { return $this->name; }
    public function getEmail(): ?string { return $this->email; }
    public function getPhone(): ?string { return $this->phone; }
    public function getCompany(): ?string { return $this->company; }
    public function getSource(): string { return $this->source; }
    public function getStatus(): string { return $this->status; }
    public function getEstimatedValue(): ?float { return $this->estimatedValue; }
    public function getOwnerId(): ?int { return $this->ownerId; }
    public function getNotes(): ?string { return $this->notes; }
    public function getConvertedAt(): ?\DateTimeInterface { return $this->convertedAt; }
    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }

    public function qualify(): void
    {
        if ($this->status === self::STATUS_DISQUALIFIED || $this->status === self::STATUS_CONVERTED) {
            throw new \DomainException("Lead cannot be qualified from status '{$this->status}'.");
        }
        $this->status = self::STATUS_QUALIFIED;
    }

    public function disqualify(): void
    {
        if ($this->status === self::STATUS_CONVERTED) {
            throw new \DomainException("A converted lead cannot be disqualified.");
        }
        $this->status = self::STATUS_DISQUALIFIED;
    }

    public function convert(): void
    {
        if ($this->status !== self::STATUS_QUALIFIED) {
            throw new \DomainException("Only qualified leads can be converted.");
        }
        $this->status      = self::STATUS_CONVERTED;
        $this->convertedAt = new \DateTimeImmutable();
    }

    public function isConverted(): bool { return $this->status === self::STATUS_CONVERTED; }
}
