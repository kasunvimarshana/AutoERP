<?php
declare(strict_types=1);
namespace Modules\Returns\Domain\Entities;

class ReturnRequest
{
    public const TYPE_PURCHASE  = 'purchase_return';
    public const TYPE_SALES     = 'sales_return';

    public const STATUS_PENDING    = 'pending';
    public const STATUS_APPROVED   = 'approved';
    public const STATUS_REJECTED   = 'rejected';
    public const STATUS_RESTOCKING = 'restocking';
    public const STATUS_RESTOCKED  = 'restocked';
    public const STATUS_COMPLETED  = 'completed';

    /** Where returned goods go: back to warehouse stock or returned to vendor */
    public const RETURN_TO_WAREHOUSE = 'warehouse';
    public const RETURN_TO_VENDOR    = 'vendor';

    public function __construct(
        private ?int $id,
        private int $tenantId,
        private string $returnType,
        private int $referenceId,
        private string $returnNumber,
        private string $status,
        private string $reason,
        private ?string $notes,
        private ?int $processedBy,
        private array $lines,
        private ?\DateTimeInterface $processedAt,
        private ?\DateTimeInterface $createdAt,
        private ?\DateTimeInterface $updatedAt,
        private string $returnTo = self::RETURN_TO_WAREHOUSE,
        private float $restockingFee = 0.0,
        private ?int $creditMemoId = null,
        private ?int $restockedBy = null,
        private ?\DateTimeInterface $restockedAt = null,
    ) {}

    public function getId(): ?int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getReturnType(): string { return $this->returnType; }
    public function getReferenceId(): int { return $this->referenceId; }
    public function getReturnNumber(): string { return $this->returnNumber; }
    public function getStatus(): string { return $this->status; }
    public function getReason(): string { return $this->reason; }
    public function getNotes(): ?string { return $this->notes; }
    public function getProcessedBy(): ?int { return $this->processedBy; }
    public function getLines(): array { return $this->lines; }
    public function getProcessedAt(): ?\DateTimeInterface { return $this->processedAt; }
    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }
    public function getReturnTo(): string { return $this->returnTo; }
    public function getRestockingFee(): float { return $this->restockingFee; }
    public function getCreditMemoId(): ?int { return $this->creditMemoId; }
    public function getRestockedBy(): ?int { return $this->restockedBy; }
    public function getRestockedAt(): ?\DateTimeInterface { return $this->restockedAt; }

    public function approve(int $processedBy): void
    {
        if ($this->status !== self::STATUS_PENDING) {
            throw new \DomainException("Can only approve a pending return.");
        }
        $this->status      = self::STATUS_APPROVED;
        $this->processedBy = $processedBy;
        $this->processedAt = new \DateTimeImmutable();
    }

    public function reject(int $processedBy): void
    {
        if ($this->status !== self::STATUS_PENDING) {
            throw new \DomainException("Can only reject a pending return.");
        }
        $this->status      = self::STATUS_REJECTED;
        $this->processedBy = $processedBy;
        $this->processedAt = new \DateTimeImmutable();
    }

    /** Mark as restocking in progress (inventory layer reversal happening) */
    public function startRestocking(): void
    {
        if ($this->status !== self::STATUS_APPROVED) {
            throw new \DomainException("Can only start restocking an approved return.");
        }
        $this->status = self::STATUS_RESTOCKING;
    }

    /** Mark restocking complete; records who performed it and when */
    public function completeRestock(int $restockedBy, ?int $creditMemoId = null): void
    {
        if ($this->status !== self::STATUS_RESTOCKING) {
            throw new \DomainException("Can only complete restock on a restocking return.");
        }
        $this->status       = self::STATUS_RESTOCKED;
        $this->restockedBy  = $restockedBy;
        $this->restockedAt  = new \DateTimeImmutable();
        $this->creditMemoId = $creditMemoId;
    }

    public function complete(): void
    {
        if (!in_array($this->status, [self::STATUS_APPROVED, self::STATUS_RESTOCKED], true)) {
            throw new \DomainException("Can only complete an approved or restocked return.");
        }
        $this->status = self::STATUS_COMPLETED;
    }
}
