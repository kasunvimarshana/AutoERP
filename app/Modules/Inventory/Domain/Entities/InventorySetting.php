<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Entities;

use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\Inventory\Domain\ValueObjects\AllocationAlgorithm;
use Modules\Inventory\Domain\ValueObjects\CycleCountMethod;
use Modules\Inventory\Domain\ValueObjects\ManagementMethod;
use Modules\Inventory\Domain\ValueObjects\StockRotationStrategy;
use Modules\Inventory\Domain\ValueObjects\ValuationMethod;

class InventorySetting
{
    private ?int $id;
    private int $tenantId;
    private string $valuationMethod;
    private string $managementMethod;
    private string $rotationStrategy;
    private string $allocationAlgorithm;
    private string $cycleCountMethod;
    private bool $negativeStockAllowed;
    private bool $trackLots;
    private bool $trackSerialNumbers;
    private bool $trackExpiry;
    private bool $autoReorder;
    private bool $lowStockAlert;
    private Metadata $metadata;
    private bool $isActive;
    private \DateTimeInterface $createdAt;
    private \DateTimeInterface $updatedAt;

    public function __construct(
        int $tenantId,
        string $valuationMethod = 'fifo',
        string $managementMethod = 'perpetual',
        string $rotationStrategy = 'fefo',
        string $allocationAlgorithm = 'fefo',
        string $cycleCountMethod = 'abc',
        bool $negativeStockAllowed = false,
        bool $trackLots = true,
        bool $trackSerialNumbers = true,
        bool $trackExpiry = true,
        bool $autoReorder = false,
        bool $lowStockAlert = true,
        ?Metadata $metadata = null,
        bool $isActive = true,
        ?int $id = null,
        ?\DateTimeInterface $createdAt = null,
        ?\DateTimeInterface $updatedAt = null
    ) {
        $this->id                   = $id;
        $this->tenantId             = $tenantId;
        $this->assertStrategyConfig($valuationMethod, $managementMethod, $rotationStrategy, $allocationAlgorithm, $cycleCountMethod);
        $this->valuationMethod      = $valuationMethod;
        $this->managementMethod     = $managementMethod;
        $this->rotationStrategy     = $rotationStrategy;
        $this->allocationAlgorithm  = $allocationAlgorithm;
        $this->cycleCountMethod     = $cycleCountMethod;
        $this->negativeStockAllowed = $negativeStockAllowed;
        $this->trackLots            = $trackLots;
        $this->trackSerialNumbers   = $trackSerialNumbers;
        $this->trackExpiry          = $trackExpiry;
        $this->autoReorder          = $autoReorder;
        $this->lowStockAlert        = $lowStockAlert;
        $this->metadata             = $metadata ?? new Metadata([]);
        $this->isActive             = $isActive;
        $this->createdAt            = $createdAt ?? new \DateTimeImmutable;
        $this->updatedAt            = $updatedAt ?? new \DateTimeImmutable;
    }

    public function getId(): ?int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getValuationMethod(): string { return $this->valuationMethod; }
    public function getManagementMethod(): string { return $this->managementMethod; }
    public function getRotationStrategy(): string { return $this->rotationStrategy; }
    public function getAllocationAlgorithm(): string { return $this->allocationAlgorithm; }
    public function getCycleCountMethod(): string { return $this->cycleCountMethod; }
    public function isNegativeStockAllowed(): bool { return $this->negativeStockAllowed; }
    public function isTrackLots(): bool { return $this->trackLots; }
    public function isTrackSerialNumbers(): bool { return $this->trackSerialNumbers; }
    public function isTrackExpiry(): bool { return $this->trackExpiry; }
    public function isAutoReorder(): bool { return $this->autoReorder; }
    public function isLowStockAlert(): bool { return $this->lowStockAlert; }
    public function getMetadata(): Metadata { return $this->metadata; }
    public function isActive(): bool { return $this->isActive; }
    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeInterface { return $this->updatedAt; }

    public function updateDetails(
        string $valuationMethod,
        string $managementMethod,
        string $rotationStrategy,
        string $allocationAlgorithm,
        string $cycleCountMethod,
        bool $negativeStockAllowed,
        bool $trackLots,
        bool $trackSerialNumbers,
        bool $trackExpiry,
        bool $autoReorder,
        bool $lowStockAlert,
        ?Metadata $metadata,
        bool $isActive
    ): void {
        $this->assertStrategyConfig($valuationMethod, $managementMethod, $rotationStrategy, $allocationAlgorithm, $cycleCountMethod);
        $this->valuationMethod      = $valuationMethod;
        $this->managementMethod     = $managementMethod;
        $this->rotationStrategy     = $rotationStrategy;
        $this->allocationAlgorithm  = $allocationAlgorithm;
        $this->cycleCountMethod     = $cycleCountMethod;
        $this->negativeStockAllowed = $negativeStockAllowed;
        $this->trackLots            = $trackLots;
        $this->trackSerialNumbers   = $trackSerialNumbers;
        $this->trackExpiry          = $trackExpiry;
        $this->autoReorder          = $autoReorder;
        $this->lowStockAlert        = $lowStockAlert;
        $this->metadata             = $metadata ?? new Metadata([]);
        $this->isActive             = $isActive;
        $this->updatedAt            = new \DateTimeImmutable;
    }

    public function activate(): void
    {
        $this->isActive  = true;
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function deactivate(): void
    {
        $this->isActive  = false;
        $this->updatedAt = new \DateTimeImmutable;
    }

    private function assertStrategyConfig(
        string $valuationMethod,
        string $managementMethod,
        string $rotationStrategy,
        string $allocationAlgorithm,
        string $cycleCountMethod,
    ): void {
        ValuationMethod::assertValid($valuationMethod);
        ManagementMethod::assertValid($managementMethod);
        StockRotationStrategy::assertValid($rotationStrategy);
        AllocationAlgorithm::assertValid($allocationAlgorithm);
        CycleCountMethod::assertValid($cycleCountMethod);
    }
}
