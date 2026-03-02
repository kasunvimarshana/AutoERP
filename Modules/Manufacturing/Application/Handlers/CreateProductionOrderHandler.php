<?php

declare(strict_types=1);

namespace Modules\Manufacturing\Application\Handlers;

use Modules\Manufacturing\Application\Commands\CreateProductionOrderCommand;
use Modules\Manufacturing\Domain\Contracts\ManufacturingRepositoryInterface;
use Modules\Manufacturing\Domain\Entities\ProductionOrder;

class CreateProductionOrderHandler
{
    public function __construct(
        private readonly ManufacturingRepositoryInterface $repository,
    ) {}

    public function handle(CreateProductionOrderCommand $command): ProductionOrder
    {
        if (bccomp($command->plannedQuantity, '0', 4) <= 0) {
            throw new \DomainException('Planned quantity must be greater than zero.');
        }

        $wastage = $command->wastagePercent;
        if (bccomp($wastage, '0', 4) < 0 || bccomp($wastage, '100', 4) > 0) {
            throw new \DomainException('Wastage percent must be between 0 and 100.');
        }

        $bom = $this->repository->findBomById($command->bomId, $command->tenantId);

        $reference = 'PRD-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));

        return $this->repository->createProductionOrder([
            'tenant_id'        => $command->tenantId,
            'reference_no'     => $reference,
            'product_id'       => $command->productId,
            'variant_id'       => $command->variantId,
            'warehouse_id'     => $command->warehouseId,
            'bom_id'           => $bom->getId(),
            'planned_quantity' => $command->plannedQuantity,
            'produced_quantity' => '0',
            'total_cost'       => '0',
            'wastage_percent'  => $wastage,
            'status'           => 'draft',
            'notes'            => $command->notes,
            'created_by'       => $command->createdBy,
        ]);
    }
}
