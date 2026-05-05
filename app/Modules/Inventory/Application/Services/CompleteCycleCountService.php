<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Core\Domain\Exceptions\NotFoundException;
use Modules\Inventory\Application\Contracts\CompleteCycleCountServiceInterface;
use Modules\Inventory\Application\Contracts\RecordStockMovementServiceInterface;
use Modules\Inventory\Domain\Events\CycleCountCompleted;
use Modules\Inventory\Domain\Entities\CycleCountHeader;
use Modules\Inventory\Domain\RepositoryInterfaces\CycleCountRepositoryInterface;

class CompleteCycleCountService implements CompleteCycleCountServiceInterface
{
    public function __construct(
        private readonly CycleCountRepositoryInterface $cycleCountRepository,
        private readonly RecordStockMovementServiceInterface $recordStockMovementService,
    ) {}

    public function execute(int $tenantId, int $countId, int $approvedByUserId, array $countedLines): CycleCountHeader
    {
        $header = $this->cycleCountRepository->findById($tenantId, $countId);
        if ($header === null) {
            throw new NotFoundException('CycleCount', $countId);
        }

        $lineUpdates = [];
        $financeAdjustments = [];
        foreach ($countedLines as $countedLine) {
            $line = null;
            foreach ($header->getLines() as $headerLine) {
                if ($headerLine->getId() === $countedLine['line_id']) {
                    $line = $headerLine;
                    break;
                }
            }

            if ($line === null) {
                continue;
            }

            $countedQty = (string) $countedLine['counted_qty'];
            $varianceQty = bcsub($countedQty, $line->getSystemQty(), 6);
            $adjustmentMovementId = null;

            if (bccomp($varianceQty, '0', 6) !== 0) {
                if ($header->getLocationId() === null) {
                    throw new \InvalidArgumentException('Cycle count location is required to post stock adjustment movements.');
                }

                $movementType = bccomp($varianceQty, '0', 6) > 0 ? 'adjustment_in' : 'adjustment_out';
                $movementQty = bccomp($varianceQty, '0', 6) > 0 ? $varianceQty : bcmul($varianceQty, '-1', 6);

                $movement = $this->recordStockMovementService->execute([
                    'tenant_id' => $tenantId,
                    'warehouse_id' => $header->getWarehouseId(),
                    'product_id' => $line->getProductId(),
                    'variant_id' => $line->getVariantId(),
                    'batch_id' => $line->getBatchId(),
                    'serial_id' => $line->getSerialId(),
                    'from_location_id' => $movementType === 'adjustment_out' ? $header->getLocationId() : null,
                    'to_location_id' => $movementType === 'adjustment_in' ? $header->getLocationId() : null,
                    'movement_type' => $movementType,
                    'reference_type' => 'cycle_count_headers',
                    'reference_id' => $countId,
                    'uom_id' => 1,
                    'quantity' => $movementQty,
                    'unit_cost' => $line->getUnitCost(),
                    'performed_by' => $approvedByUserId,
                    'notes' => 'Cycle count adjustment for line '.$line->getId(),
                ]);

                $adjustmentMovementId = $movement->getId();

                $product = DB::table('products')
                    ->where('tenant_id', $tenantId)
                    ->where('id', $line->getProductId())
                    ->select('inventory_account_id', 'expense_account_id')
                    ->first();

                $financeAdjustments[] = [
                    'product_id' => $line->getProductId(),
                    'variance_qty' => $varianceQty,
                    'unit_cost' => $line->getUnitCost(),
                    'amount' => bcmul($movementQty, $line->getUnitCost(), 6),
                    'direction' => $movementType === 'adjustment_in' ? 'increase' : 'decrease',
                    'inventory_account_id' => $product !== null ? $product->inventory_account_id : null,
                    'expense_account_id' => $product !== null ? $product->expense_account_id : null,
                ];
            }

            $lineUpdates[] = [
                'line_id' => (int) $line->getId(),
                'counted_qty' => $countedQty,
                'adjustment_movement_id' => $adjustmentMovementId,
            ];
        }

        $completed = $this->cycleCountRepository->complete($tenantId, $countId, $lineUpdates, $approvedByUserId);
        if ($completed === null) {
            throw new NotFoundException('CycleCount', $countId);
        }

        if (! empty($financeAdjustments)) {
            Event::dispatch(new CycleCountCompleted(
                tenantId: $tenantId,
                cycleCountId: $countId,
                warehouseId: $header->getWarehouseId(),
                locationId: $header->getLocationId(),
                countDate: $completed->getCountedAt() ?? now()->toDateString(),
                adjustments: $financeAdjustments,
                createdBy: $approvedByUserId,
            ));
        }

        return $completed;
    }
}
