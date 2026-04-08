<?php

declare(strict_types=1);

namespace Modules\Batch\Domain\ValueObjects;

use InvalidArgumentException;

// ── BatchStatus ──────────────────────────────────────────────────────────────
final class BatchStatus
{
    public const ACTIVE     = 'active';
    public const QUARANTINE = 'quarantine';
    public const HOLD       = 'hold';
    public const REJECTED   = 'rejected';
    public const CONSUMED   = 'consumed';
    public const EXPIRED    = 'expired';
    public const RECALLED   = 'recalled';

    public const VALID = [
        self::ACTIVE, self::QUARANTINE, self::HOLD,
        self::REJECTED, self::CONSUMED, self::EXPIRED, self::RECALLED,
    ];
    public const USABLE = [self::ACTIVE];
    public const BLOCKED = [self::QUARANTINE, self::HOLD, self::REJECTED, self::RECALLED];

    public function __construct(private readonly string $value)
    {
        if (!in_array($value, self::VALID, true)) {
            throw new InvalidArgumentException("Invalid batch status: {$value}");
        }
    }

    public function value(): string      { return $this->value; }
    public function isActive(): bool     { return $this->value === self::ACTIVE; }
    public function isUsable(): bool     { return in_array($this->value, self::USABLE, true); }
    public function isBlocked(): bool    { return in_array($this->value, self::BLOCKED, true); }
    public function isExpired(): bool    { return $this->value === self::EXPIRED; }
    public function __toString(): string { return $this->value; }
}

// ── QcStatus ─────────────────────────────────────────────────────────────────
final class QcStatus
{
    public const PENDING = 'pending';
    public const PASSED  = 'passed';
    public const FAILED  = 'failed';
    public const WAIVED  = 'waived';
    public const VALID   = [self::PENDING, self::PASSED, self::FAILED, self::WAIVED];

    public function __construct(private readonly string $value)
    {
        if (!in_array($value, self::VALID, true)) {
            throw new InvalidArgumentException("Invalid QC status: {$value}");
        }
    }

    public function value(): string     { return $this->value; }
    public function isPending(): bool   { return $this->value === self::PENDING; }
    public function isPassed(): bool    { return $this->value === self::PASSED; }
    public function isFailed(): bool    { return $this->value === self::FAILED; }
    public function isApproved(): bool  { return in_array($this->value, [self::PASSED, self::WAIVED], true); }
    public function __toString(): string { return $this->value; }
}


namespace Modules\Batch\Domain\Entities;

use DateTimeImmutable;
use DateTimeInterface;
use Modules\Batch\Domain\ValueObjects\BatchStatus;
use Modules\Batch\Domain\ValueObjects\QcStatus;

/**
 * Batch Domain Entity
 * Represents a manufacturing batch — a group produced under the same conditions.
 */
final class Batch
{
    public function __construct(
        private readonly int    $tenantId,
        private readonly int    $productId,
        private readonly string $batchNumber,
        private BatchStatus     $status,
        private QcStatus        $qcStatus,
        private ?int            $productVariantId  = null,
        private ?string         $externalBatchRef  = null,
        private ?\DateTimeInterface $manufactureDate = null,
        private ?\DateTimeInterface $expiryDate      = null,
        private ?\DateTimeInterface $bestBeforeDate  = null,
        private ?\DateTimeInterface $receivedDate    = null,
        private ?string         $supplierName      = null,
        private ?string         $countryOfOrigin   = null,
        private ?string         $certificateNumber = null,
        private ?string         $qcNotes           = null,
        private ?\DateTimeInterface $qcTestedAt    = null,
        private ?int            $qcTestedBy        = null,
        private ?float          $unitCost          = null,
        private string          $currency          = 'USD',
        private ?array          $landedCosts       = null,
        private ?string         $notes             = null,
        private ?array          $customFields      = null,
        private ?int            $id                = null,
        private ?\DateTimeInterface $createdAt     = null,
        private ?\DateTimeInterface $updatedAt     = null,
    ) {
        $this->createdAt = $createdAt ?? new DateTimeImmutable();
        $this->updatedAt = $updatedAt ?? new DateTimeImmutable();
    }

    // ── Getters ──────────────────────────────────────────────────────────────
    public function getId(): ?int                  { return $this->id; }
    public function getTenantId(): int             { return $this->tenantId; }
    public function getProductId(): int            { return $this->productId; }
    public function getBatchNumber(): string       { return $this->batchNumber; }
    public function getStatus(): BatchStatus       { return $this->status; }
    public function getQcStatus(): QcStatus        { return $this->qcStatus; }
    public function getExpiryDate(): ?\DateTimeInterface { return $this->expiryDate; }
    public function getManufactureDate(): ?\DateTimeInterface { return $this->manufactureDate; }
    public function getUnitCost(): ?float          { return $this->unitCost; }
    public function getCurrency(): string          { return $this->currency; }
    public function getNotes(): ?string            { return $this->notes; }
    public function getCustomFields(): ?array      { return $this->customFields; }

    // ── Business logic ────────────────────────────────────────────────────────
    public function isExpired(): bool
    {
        return $this->expiryDate !== null && $this->expiryDate < new DateTimeImmutable();
    }

    public function isExpiringSoon(int $warningDays = 30): bool
    {
        if ($this->expiryDate === null) return false;
        $threshold = (new DateTimeImmutable())->modify("+{$warningDays} days");
        return !$this->isExpired() && $this->expiryDate <= $threshold;
    }

    public function isUsable(): bool
    {
        return $this->status->isUsable() && $this->qcStatus->isApproved() && !$this->isExpired();
    }

    public function hold(string $reason = ''): void
    {
        $this->status    = new BatchStatus(BatchStatus::HOLD);
        $this->qcNotes   = $reason ?: $this->qcNotes;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function quarantine(?string $reason = null): void
    {
        $this->status    = new BatchStatus(BatchStatus::QUARANTINE);
        if ($reason) $this->qcNotes = $reason;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function passQc(?int $testedBy = null, ?string $notes = null): void
    {
        $this->qcStatus  = new QcStatus(QcStatus::PASSED);
        $this->status    = new BatchStatus(BatchStatus::ACTIVE);
        $this->qcTestedBy = $testedBy;
        $this->qcTestedAt = new DateTimeImmutable();
        if ($notes) $this->qcNotes = $notes;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function failQc(?int $testedBy = null, ?string $notes = null): void
    {
        $this->qcStatus  = new QcStatus(QcStatus::FAILED);
        $this->status    = new BatchStatus(BatchStatus::REJECTED);
        $this->qcTestedBy = $testedBy;
        $this->qcTestedAt = new DateTimeImmutable();
        if ($notes) $this->qcNotes = $notes;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function recall(?string $reason = null): void
    {
        $this->status    = new BatchStatus(BatchStatus::RECALLED);
        if ($reason) $this->qcNotes = $reason;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function markConsumed(): void
    {
        $this->status    = new BatchStatus(BatchStatus::CONSUMED);
        $this->updatedAt = new DateTimeImmutable();
    }

    public function assignId(int $id): void
    {
        if ($this->id !== null) throw new \LogicException('Batch ID already assigned');
        $this->id = $id;
    }
}


namespace Modules\Batch\Domain\Events;

final class BatchCreated
{
    public readonly \DateTimeImmutable $occurredAt;
    public function __construct(public readonly \Modules\Batch\Domain\Entities\Batch $batch)
    {
        $this->occurredAt = new \DateTimeImmutable();
    }
}

final class BatchQcStatusChanged
{
    public readonly \DateTimeImmutable $occurredAt;
    public function __construct(
        public readonly \Modules\Batch\Domain\Entities\Batch $batch,
        public readonly string $previousStatus,
        public readonly string $newStatus,
    ) { $this->occurredAt = new \DateTimeImmutable(); }
}

final class BatchExpired
{
    public readonly \DateTimeImmutable $occurredAt;
    public function __construct(
        public readonly int $batchId,
        public readonly int $tenantId,
        public readonly int $productId,
    ) { $this->occurredAt = new \DateTimeImmutable(); }
}


namespace Modules\Batch\Domain\Exceptions;

final class BatchNotFoundException extends \RuntimeException
{
    public function __construct(int|string $id)
    { parent::__construct("Batch not found: {$id}"); }
}

final class BatchNotUsableException extends \RuntimeException
{
    public function __construct(string $batchNumber, string $reason)
    { parent::__construct("Batch '{$batchNumber}' not usable: {$reason}"); }
}


namespace Modules\Batch\Domain\RepositoryInterfaces;

use Modules\Batch\Domain\Entities\Batch;
use Modules\Core\Domain\RepositoryInterfaces\BaseRepositoryInterface;

interface BatchRepositoryInterface extends BaseRepositoryInterface
{
    public function findById(int $id): ?Batch;
    public function findByNumber(string $batchNumber, int $tenantId): ?Batch;
    public function findByProduct(int $productId, int $tenantId, array $filters = []): mixed;
    public function findExpiring(int $tenantId, int $warningDays = 30): array;
    public function findQcPending(int $tenantId): array;
    public function save(mixed $entity): Batch;
}
