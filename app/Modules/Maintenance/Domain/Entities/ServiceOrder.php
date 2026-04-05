<?php
declare(strict_types=1);
namespace Modules\Maintenance\Domain\Entities;

/**
 * A service/maintenance order for an asset or piece of equipment.
 * type: corrective | preventive | predictive | inspection
 * status: draft | scheduled | in_progress | on_hold | completed | cancelled
 * priority: low | medium | high | critical
 */
class ServiceOrder
{
    public const TYPE_CORRECTIVE  = 'corrective';
    public const TYPE_PREVENTIVE  = 'preventive';
    public const TYPE_PREDICTIVE  = 'predictive';
    public const TYPE_INSPECTION  = 'inspection';

    public const STATUS_DRAFT       = 'draft';
    public const STATUS_SCHEDULED   = 'scheduled';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_ON_HOLD     = 'on_hold';
    public const STATUS_COMPLETED   = 'completed';
    public const STATUS_CANCELLED   = 'cancelled';

    public const PRIORITY_LOW      = 'low';
    public const PRIORITY_MEDIUM   = 'medium';
    public const PRIORITY_HIGH     = 'high';
    public const PRIORITY_CRITICAL = 'critical';

    public function __construct(
        private ?int $id,
        private int $tenantId,
        private string $orderNumber,
        private string $type,
        private string $status,
        private string $priority,
        private string $title,
        private ?string $description,
        private ?int $assetId,           // fixed asset
        private ?int $warehouseId,       // location context
        private ?int $assignedTo,        // technician / employee
        private ?int $customerId,        // for customer-facing service
        private float $estimatedHours,
        private float $actualHours,
        private float $laborCost,
        private float $partsCost,
        private ?\DateTimeInterface $scheduledAt,
        private ?\DateTimeInterface $startedAt,
        private ?\DateTimeInterface $completedAt,
        private ?\DateTimeInterface $createdAt,
        private ?\DateTimeInterface $updatedAt,
    ) {}

    public function getId(): ?int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getOrderNumber(): string { return $this->orderNumber; }
    public function getType(): string { return $this->type; }
    public function getStatus(): string { return $this->status; }
    public function getPriority(): string { return $this->priority; }
    public function getTitle(): string { return $this->title; }
    public function getDescription(): ?string { return $this->description; }
    public function getAssetId(): ?int { return $this->assetId; }
    public function getWarehouseId(): ?int { return $this->warehouseId; }
    public function getAssignedTo(): ?int { return $this->assignedTo; }
    public function getCustomerId(): ?int { return $this->customerId; }
    public function getEstimatedHours(): float { return $this->estimatedHours; }
    public function getActualHours(): float { return $this->actualHours; }
    public function getLaborCost(): float { return $this->laborCost; }
    public function getPartsCost(): float { return $this->partsCost; }
    public function getTotalCost(): float { return $this->laborCost + $this->partsCost; }
    public function getScheduledAt(): ?\DateTimeInterface { return $this->scheduledAt; }
    public function getStartedAt(): ?\DateTimeInterface { return $this->startedAt; }
    public function getCompletedAt(): ?\DateTimeInterface { return $this->completedAt; }
    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }

    public function start(): void
    {
        if (!in_array($this->status, [self::STATUS_SCHEDULED, self::STATUS_ON_HOLD], true)) {
            throw new \DomainException("Cannot start a service order with status '{$this->status}'.");
        }
        $this->status    = self::STATUS_IN_PROGRESS;
        $this->startedAt = $this->startedAt ?? new \DateTimeImmutable();
    }

    public function complete(float $actualHours, float $laborCost, float $partsCost): void
    {
        if ($this->status !== self::STATUS_IN_PROGRESS) {
            throw new \DomainException("Only in-progress orders can be completed.");
        }
        $this->status      = self::STATUS_COMPLETED;
        $this->actualHours = $actualHours;
        $this->laborCost   = $laborCost;
        $this->partsCost   = $partsCost;
        $this->completedAt = new \DateTimeImmutable();
    }

    public function cancel(): void
    {
        if ($this->status === self::STATUS_COMPLETED) {
            throw new \DomainException("Completed orders cannot be cancelled.");
        }
        $this->status = self::STATUS_CANCELLED;
    }

    public function isCompleted(): bool { return $this->status === self::STATUS_COMPLETED; }
}
