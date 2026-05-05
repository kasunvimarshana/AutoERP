<?php

declare(strict_types=1);

namespace Modules\ServiceCenter\Domain\Entities;

final class ServicePartUsage
{
    private string $id;
    private string $serviceOrderId;
    private ?string $inventoryItemId;
    private string $partName;
    private string $partNumber;
    private int $quantity;
    private string $unitCost;
    private string $totalCost;

    public function __construct(
        string $id,
        string $serviceOrderId,
        ?string $inventoryItemId,
        string $partName,
        string $partNumber,
        int $quantity,
        string $unitCost,
        string $totalCost,
    ) {
        $this->id = $id;
        $this->serviceOrderId = $serviceOrderId;
        $this->inventoryItemId = $inventoryItemId;
        $this->partName = $partName;
        $this->partNumber = $partNumber;
        $this->quantity = $quantity;
        $this->unitCost = $unitCost;
        $this->totalCost = $totalCost;
    }

    public function getId(): string { return $this->id; }
    public function getServiceOrderId(): string { return $this->serviceOrderId; }
    public function getInventoryItemId(): ?string { return $this->inventoryItemId; }
    public function getPartName(): string { return $this->partName; }
    public function getPartNumber(): string { return $this->partNumber; }
    public function getQuantity(): int { return $this->quantity; }
    public function getUnitCost(): string { return $this->unitCost; }
    public function getTotalCost(): string { return $this->totalCost; }
}
