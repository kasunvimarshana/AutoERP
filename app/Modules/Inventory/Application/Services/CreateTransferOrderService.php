<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services;

use Modules\Inventory\Application\Contracts\CreateTransferOrderServiceInterface;
use Modules\Inventory\Domain\Entities\TransferOrder;
use Modules\Inventory\Domain\Entities\TransferOrderLine;
use Modules\Inventory\Domain\RepositoryInterfaces\TransferOrderRepositoryInterface;

class CreateTransferOrderService implements CreateTransferOrderServiceInterface
{
    public function __construct(private readonly TransferOrderRepositoryInterface $transferOrderRepository) {}

    public function execute(array $data): TransferOrder
    {
        $lines = [];
        foreach ($data['lines'] as $line) {
            $lines[] = new TransferOrderLine(
                tenantId: (int) $data['tenant_id'],
                productId: (int) $line['product_id'],
                variantId: isset($line['variant_id']) ? (int) $line['variant_id'] : null,
                batchId: isset($line['batch_id']) ? (int) $line['batch_id'] : null,
                serialId: isset($line['serial_id']) ? (int) $line['serial_id'] : null,
                fromLocationId: isset($line['from_location_id']) ? (int) $line['from_location_id'] : null,
                toLocationId: isset($line['to_location_id']) ? (int) $line['to_location_id'] : null,
                uomId: (int) $line['uom_id'],
                requestedQty: (string) $line['requested_qty'],
                shippedQty: (string) ($line['shipped_qty'] ?? '0.000000'),
                receivedQty: (string) ($line['received_qty'] ?? '0.000000'),
                unitCost: isset($line['unit_cost']) ? (string) $line['unit_cost'] : null,
            );
        }

        $order = new TransferOrder(
            tenantId: (int) $data['tenant_id'],
            fromWarehouseId: (int) $data['from_warehouse_id'],
            toWarehouseId: (int) $data['to_warehouse_id'],
            transferNumber: (string) $data['transfer_number'],
            status: (string) ($data['status'] ?? 'draft'),
            requestDate: (string) $data['request_date'],
            expectedDate: $data['expected_date'] ?? null,
            shippedDate: $data['shipped_date'] ?? null,
            receivedDate: $data['received_date'] ?? null,
            notes: $data['notes'] ?? null,
            metadata: is_array($data['metadata'] ?? null) ? $data['metadata'] : null,
            lines: $lines,
            orgUnitId: isset($data['org_unit_id']) ? (int) $data['org_unit_id'] : null,
        );

        return $this->transferOrderRepository->create($order);
    }
}
