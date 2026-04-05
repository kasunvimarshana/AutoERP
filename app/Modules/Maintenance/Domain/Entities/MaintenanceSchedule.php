<?php
declare(strict_types=1);
namespace Modules\Maintenance\Domain\Entities;

/**
 * A recurring maintenance schedule for an asset.
 * frequencyUnit: day | week | month | year
 */
class MaintenanceSchedule
{
    public function __construct(
        private ?int $id,
        private int $tenantId,
        private string $name,
        private ?int $assetId,
        private string $maintenanceType,   // preventive|inspection
        private int $frequencyValue,
        private string $frequencyUnit,
        private ?\DateTimeInterface $lastRunAt,
        private ?\DateTimeInterface $nextRunAt,
        private bool $isActive,
        private ?\DateTimeInterface $createdAt,
        private ?\DateTimeInterface $updatedAt,
    ) {}

    public function getId(): ?int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getName(): string { return $this->name; }
    public function getAssetId(): ?int { return $this->assetId; }
    public function getMaintenanceType(): string { return $this->maintenanceType; }
    public function getFrequencyValue(): int { return $this->frequencyValue; }
    public function getFrequencyUnit(): string { return $this->frequencyUnit; }
    public function getLastRunAt(): ?\DateTimeInterface { return $this->lastRunAt; }
    public function getNextRunAt(): ?\DateTimeInterface { return $this->nextRunAt; }
    public function isActive(): bool { return $this->isActive; }
    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }

    public function activate(): void { $this->isActive = true; }
    public function deactivate(): void { $this->isActive = false; }

    public function recordRun(\DateTimeInterface $ranAt): void
    {
        $this->lastRunAt = $ranAt;
        $this->nextRunAt = $this->computeNextRun($ranAt);
    }

    private function computeNextRun(\DateTimeInterface $from): \DateTimeImmutable
    {
        $modifier = "+{$this->frequencyValue} {$this->frequencyUnit}";
        return (new \DateTimeImmutable($from->format('Y-m-d H:i:s')))->modify($modifier);
    }
}
