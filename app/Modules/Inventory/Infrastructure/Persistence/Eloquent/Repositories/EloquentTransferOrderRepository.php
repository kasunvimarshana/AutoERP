<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Facades\DB;
use Modules\Inventory\Domain\Entities\TransferOrder;
use Modules\Inventory\Domain\Entities\TransferOrderLine;
use Modules\Inventory\Domain\RepositoryInterfaces\TransferOrderRepositoryInterface;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\TransferOrderModel;

class EloquentTransferOrderRepository implements TransferOrderRepositoryInterface
{
    public function __construct(private readonly TransferOrderModel $transferOrderModel) {}

    public function create(TransferOrder $transferOrder): TransferOrder
    {
        /** @var TransferOrderModel $model */
        $model = $this->transferOrderModel->newQuery()->create([
            'tenant_id' => $transferOrder->getTenantId(),
            'org_unit_id' => $transferOrder->getOrgUnitId(),
            'from_warehouse_id' => $transferOrder->getFromWarehouseId(),
            'to_warehouse_id' => $transferOrder->getToWarehouseId(),
            'transfer_number' => $transferOrder->getTransferNumber(),
            'status' => $transferOrder->getStatus(),
            'request_date' => $transferOrder->getRequestDate(),
            'expected_date' => $transferOrder->getExpectedDate(),
            'shipped_date' => $transferOrder->getShippedDate(),
            'received_date' => $transferOrder->getReceivedDate(),
            'notes' => $transferOrder->getNotes(),
            'metadata' => $transferOrder->getMetadata(),
        ]);

        foreach ($transferOrder->getLines() as $line) {
            $model->lines()->create([
                'tenant_id' => $transferOrder->getTenantId(),
                'product_id' => $line->getProductId(),
                'variant_id' => $line->getVariantId(),
                'batch_id' => $line->getBatchId(),
                'serial_id' => $line->getSerialId(),
                'from_location_id' => $line->getFromLocationId(),
                'to_location_id' => $line->getToLocationId(),
                'uom_id' => $line->getUomId(),
                'requested_qty' => $line->getRequestedQty(),
                'shipped_qty' => $line->getShippedQty(),
                'received_qty' => $line->getReceivedQty(),
                'unit_cost' => $line->getUnitCost(),
            ]);
        }

        /** @var TransferOrderModel $fresh */
        $fresh = $model->fresh(['lines']);

        return $this->mapToEntity($fresh);
    }

    public function findById(int $tenantId, int $transferOrderId): ?TransferOrder
    {
        /** @var TransferOrderModel|null $model */
        $model = $this->transferOrderModel->newQuery()
            ->with('lines')
            ->where('tenant_id', $tenantId)
            ->where('id', $transferOrderId)
            ->first();

        return $model !== null ? $this->mapToEntity($model) : null;
    }

    public function paginate(int $tenantId, int $perPage, int $page): mixed
    {
        return $this->transferOrderModel->newQuery()
            ->where('tenant_id', $tenantId)
            ->orderByDesc('id')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    public function markAsReceived(int $tenantId, int $transferOrderId, array $receivedLines, string $receivedDate): ?TransferOrder
    {
        return DB::transaction(function () use ($tenantId, $transferOrderId, $receivedLines, $receivedDate): ?TransferOrder {
            /** @var TransferOrderModel|null $model */
            $model = $this->transferOrderModel->newQuery()
                ->with('lines')
                ->where('tenant_id', $tenantId)
                ->where('id', $transferOrderId)
                ->lockForUpdate()
                ->first();

            if ($model === null) {
                return null;
            }

            $linesById = $model->lines->keyBy('id');

            foreach ($receivedLines as $receivedLine) {
                $lineId = (int) ($receivedLine['line_id'] ?? 0);
                $line = $linesById->get($lineId);
                if ($line === null) {
                    continue;
                }

                $line->received_qty = $receivedLine['received_qty'];
                $line->shipped_qty = $receivedLine['received_qty'];
                $line->save();
            }

            $model->status = 'received';
            $model->received_date = $receivedDate;
            $model->shipped_date = $receivedDate;
            $model->save();

            /** @var TransferOrderModel $fresh */
            $fresh = $model->fresh(['lines']);

            return $this->mapToEntity($fresh);
        });
    }

    public function markAsApproved(int $tenantId, int $transferOrderId): ?TransferOrder
    {
        /** @var TransferOrderModel|null $model */
        $model = $this->transferOrderModel->newQuery()
            ->with('lines')
            ->where('tenant_id', $tenantId)
            ->where('id', $transferOrderId)
            ->first();

        if ($model === null) {
            return null;
        }

        $model->status = 'approved';
        $model->save();

        /** @var TransferOrderModel $fresh */
        $fresh = $model->fresh(['lines']);

        return $this->mapToEntity($fresh);
    }

    private function mapToEntity(TransferOrderModel $model): TransferOrder
    {
        $lines = [];
        foreach ($model->lines as $line) {
            $lines[] = new TransferOrderLine(
                tenantId: (int) $line->tenant_id,
                productId: (int) $line->product_id,
                variantId: $line->variant_id !== null ? (int) $line->variant_id : null,
                batchId: $line->batch_id !== null ? (int) $line->batch_id : null,
                serialId: $line->serial_id !== null ? (int) $line->serial_id : null,
                fromLocationId: $line->from_location_id !== null ? (int) $line->from_location_id : null,
                toLocationId: $line->to_location_id !== null ? (int) $line->to_location_id : null,
                uomId: (int) $line->uom_id,
                requestedQty: (string) $line->requested_qty,
                shippedQty: (string) $line->shipped_qty,
                receivedQty: (string) $line->received_qty,
                unitCost: $line->unit_cost !== null ? (string) $line->unit_cost : null,
                id: (int) $line->id,
            );
        }

        return new TransferOrder(
            tenantId: (int) $model->tenant_id,
            fromWarehouseId: (int) $model->from_warehouse_id,
            toWarehouseId: (int) $model->to_warehouse_id,
            transferNumber: (string) $model->transfer_number,
            status: (string) $model->status,
            requestDate: (string) $model->request_date,
            expectedDate: $model->expected_date !== null ? (string) $model->expected_date : null,
            shippedDate: $model->shipped_date !== null ? (string) $model->shipped_date : null,
            receivedDate: $model->received_date !== null ? (string) $model->received_date : null,
            notes: $model->notes,
            metadata: is_array($model->metadata) ? $model->metadata : null,
            lines: $lines,
            orgUnitId: $model->org_unit_id !== null ? (int) $model->org_unit_id : null,
            id: (int) $model->id,
        );
    }
}
