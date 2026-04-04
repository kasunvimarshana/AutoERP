<?php
namespace Modules\Inventory\Domain\Entities;

use Modules\Core\Domain\Entities\BaseEntity;
use Modules\Inventory\Domain\ValueObjects\AllocationAlgorithm;
use Modules\Inventory\Domain\ValueObjects\CycleCountMethod;
use Modules\Inventory\Domain\ValueObjects\ManagementMethod;
use Modules\Inventory\Domain\ValueObjects\StockRotationStrategy;
use Modules\Inventory\Domain\ValueObjects\ValuationMethod;

class InventorySetting extends BaseEntity
{
    public function __construct(
        ?int $id,
        public readonly int $tenantId,
        public readonly string $valuationMethod = ValuationMethod::FIFO,
        public readonly string $managementMethod = ManagementMethod::STANDARD,
        public readonly string $stockRotationStrategy = StockRotationStrategy::FIFO,
        public readonly string $allocationAlgorithm = AllocationAlgorithm::FIFO,
        public readonly string $cycleCountMethod = CycleCountMethod::FULL,
        public readonly bool $negativeStockAllowed = false,
        public readonly bool $autoReorderEnabled = false,
        public readonly ?float $defaultReorderPoint = null,
        public readonly ?float $defaultReorderQty = null,
    ) {
        parent::__construct($id);
        $this->assertStrategyConfig();
    }

    private function assertStrategyConfig(): void
    {
        ValuationMethod::assertValid($this->valuationMethod);
        ManagementMethod::assertValid($this->managementMethod);
        StockRotationStrategy::assertValid($this->stockRotationStrategy);
        AllocationAlgorithm::assertValid($this->allocationAlgorithm);
        CycleCountMethod::assertValid($this->cycleCountMethod);
    }
}
