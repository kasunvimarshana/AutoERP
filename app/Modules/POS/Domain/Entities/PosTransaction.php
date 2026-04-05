<?php
declare(strict_types=1);
namespace Modules\POS\Domain\Entities;

/**
 * A POS sale or return transaction.
 * status: pending | completed | voided
 * type: sale | refund
 */
class PosTransaction
{
    public const STATUS_PENDING   = 'pending';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_VOIDED    = 'voided';
    public const TYPE_SALE        = 'sale';
    public const TYPE_REFUND      = 'refund';

    public function __construct(
        private ?int $id,
        private int $tenantId,
        private int $sessionId,
        private ?int $customerId,
        private string $type,
        private string $status,
        private string $currency,
        private float $subtotal,
        private float $taxTotal,
        private float $discountTotal,
        private float $total,
        private string $paymentMethod, // cash|card|split|voucher|credit
        private ?float $amountTendered,
        private ?float $changeGiven,
        private ?string $reference,
        private ?string $notes,
        private array $lines,
        private ?\DateTimeInterface $createdAt,
        private ?\DateTimeInterface $updatedAt,
    ) {}

    public function getId(): ?int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getSessionId(): int { return $this->sessionId; }
    public function getCustomerId(): ?int { return $this->customerId; }
    public function getType(): string { return $this->type; }
    public function getStatus(): string { return $this->status; }
    public function getCurrency(): string { return $this->currency; }
    public function getSubtotal(): float { return $this->subtotal; }
    public function getTaxTotal(): float { return $this->taxTotal; }
    public function getDiscountTotal(): float { return $this->discountTotal; }
    public function getTotal(): float { return $this->total; }
    public function getPaymentMethod(): string { return $this->paymentMethod; }
    public function getAmountTendered(): ?float { return $this->amountTendered; }
    public function getChangeGiven(): ?float { return $this->changeGiven; }
    public function getReference(): ?string { return $this->reference; }
    public function getNotes(): ?string { return $this->notes; }
    public function getLines(): array { return $this->lines; }
    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }

    public function isCompleted(): bool { return $this->status === self::STATUS_COMPLETED; }

    public function void(): void
    {
        if ($this->status !== self::STATUS_COMPLETED) {
            throw new \DomainException("Only completed transactions can be voided.");
        }
        $this->status = self::STATUS_VOIDED;
    }

    public function complete(): void
    {
        if ($this->status !== self::STATUS_PENDING) {
            throw new \DomainException("Only pending transactions can be completed.");
        }
        $this->status = self::STATUS_COMPLETED;
    }
}
