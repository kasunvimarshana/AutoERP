<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services;

use Modules\Core\Domain\Exceptions\NotFoundException;
use Modules\Inventory\Application\Contracts\ReceiveTransferOrderServiceInterface;
use Modules\Inventory\Application\Contracts\RecordStockMovementServiceInterface;
use Modules\Inventory\Domain\Entities\TransferOrder;
use Modules\Inventory\Domain\RepositoryInterfaces\TransferOrderRepositoryInterface;

class ReceiveTransferOrderService implements ReceiveTransferOrderServiceInterface
{
    public function __construct(
        private readonly TransferOrderRepositoryInterface $transferOrderRepository,
        private readonly RecordStockMovementServiceInterface $recordStockMovementService,
    ) {}

    public function execute(int $tenantId, int $transferOrderId, array $receivedLines): TransferOrder
    {
        $existing = $this->transferOrderRepository->findById($tenantId, $transferOrderId);
        if ($existing === null) {
            throw new NotFoundException('TransferOrder', $transferOrderId);
        }

        foreach ($receivedLines as $receivedLine) {
            $line = null;
            foreach ($existing->getLines() as $orderLine) {
                if ($orderLine->getId() === $receivedLine['line_id']) {
                    $line = $orderLine;
                    break;
                }
            }

            if ($line === null || bccomp($receivedLine['received_qty'], '0', 6) <= 0) {
                continue;
            }

            $this->recordStockMovementService->execute([
                'tenant_id' => $tenantId,
                'warehouse_id' => $existing->getFromWarehouseId(),
                'product_id' => $line->getProductId(),
                'variant_id' => $line->getVariantId(),
                'batch_id' => $line->getBatchId(),
                'serial_id' => $line->getSerialId(),
                'from_location_id' => $line->getFromLocationId(),
                'to_location_id' => null,
                'movement_type' => 'shipment',
                'uom_id' => $line->getUomId(),
                'quantity' => $receivedLine['received_qty'],
                'unit_cost' => $line->getUnitCost(),
                'notes' => 'Transfer order shipment: '.$existing->getTransferNumber(),
            ]);

            $this->recordStockMovementService->execute([
                'tenant_id' => $tenantId,
                'warehouse_id' => $existing->getToWarehouseId(),
                'product_id' => $line->getProductId(),
                'variant_id' => $line->getVariantId(),
                'batch_id' => $line->getBatchId(),
                'serial_id' => $line->getSerialId(),
                'from_location_id' => null,
                'to_location_id' => $line->getToLocationId(),
                'movement_type' => 'receipt',
                'uom_id' => $line->getUomId(),
                'quantity' => $receivedLine['received_qty'],
                'unit_cost' => $line->getUnitCost(),
                'notes' => 'Transfer order receipt: '.$existing->getTransferNumber(),
            ]);
        }

        $order = $this->transferOrderRepository->markAsReceived($tenantId, $transferOrderId, $receivedLines, now()->toDateString());
        if ($order === null) {
            throw new NotFoundException('TransferOrder', $transferOrderId);
        }

        return $order;
    }
}
