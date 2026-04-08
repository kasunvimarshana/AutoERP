<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Core\Application\Services\BaseService;
use Modules\Inventory\Application\Contracts\CycleCountServiceInterface;
use Modules\Inventory\Domain\Contracts\Repositories\CycleCountRepositoryInterface;
use Modules\Inventory\Domain\Contracts\Repositories\InventoryItemRepositoryInterface;
use Modules\Inventory\Domain\Events\CycleCountCompleted;
use Modules\Inventory\Domain\Exceptions\CycleCountNotFoundException;

class CycleCountService extends BaseService implements CycleCountServiceInterface
{
    public function __construct(
        CycleCountRepositoryInterface $repository,
        private readonly InventoryItemRepositoryInterface $inventoryItemRepository,
    ) {
        parent::__construct($repository);
    }

    /**
     * Default execute handler.
     */
    protected function handle(array $data): mixed
    {
        return $this->createCycleCount($data);
    }

    /**
     * Create a new cycle count with its lines (status = draft).
     * Each line captures the system quantity at the time of count creation.
     */
    public function createCycleCount(array $data): mixed
    {
        return DB::transaction(function () use ($data) {
            $lines = $data['lines'] ?? [];

            $cycleCount = $this->repository->create([
                'tenant_id'    => $data['tenant_id'],
                'count_number' => $data['count_number'],
                'warehouse_id' => $data['warehouse_id'],
                'location_id'  => $data['location_id'] ?? null,
                'status'       => 'draft',
                'counted_at'   => $data['counted_at'],
                'completed_at' => null,
                'counted_by'   => $data['counted_by'],
                'notes'        => $data['notes'] ?? null,
            ]);

            foreach ($lines as $line) {
                // Capture current system quantity from inventory_items
                $item = $this->inventoryItemRepository->findByProductWarehouse(
                    $line['product_id'],
                    $data['warehouse_id'],
                    $line['variant_id'] ?? null,
                );
                $systemQty = $item ? (float) $item->quantity_on_hand : 0.0;

                DB::table('cycle_count_lines')->insert([
                    'id'              => Str::uuid(),
                    'tenant_id'       => $data['tenant_id'],
                    'cycle_count_id'  => $cycleCount->id,
                    'product_id'      => $line['product_id'],
                    'variant_id'      => $line['variant_id'] ?? null,
                    'batch_lot_id'    => $line['batch_lot_id'] ?? null,
                    'system_quantity' => $systemQty,
                    'counted_quantity' => 0,
                    'variance'        => 0,
                    'status'          => 'pending',
                    'notes'           => $line['notes'] ?? null,
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);
            }

            return $cycleCount;
        });
    }

    /**
     * Submit counted quantities and compute variance for each line.
     * Moves the cycle count to 'submitted' status.
     */
    public function submitCount(string $cycleCountId, array $lines): mixed
    {
        return DB::transaction(function () use ($cycleCountId, $lines) {
            $cycleCount = $this->repository->find($cycleCountId);
            if (! $cycleCount) {
                throw new CycleCountNotFoundException($cycleCountId);
            }

            foreach ($lines as $line) {
                $countedQty  = (float) ($line['counted_quantity'] ?? 0);
                $systemQty   = DB::table('cycle_count_lines')
                    ->where('id', $line['id'])
                    ->value('system_quantity') ?? 0.0;
                $variance    = $countedQty - (float) $systemQty;

                DB::table('cycle_count_lines')
                    ->where('id', $line['id'])
                    ->where('cycle_count_id', $cycleCountId)
                    ->update([
                        'counted_quantity' => $countedQty,
                        'variance'         => $variance,
                        'status'           => abs($variance) > 0.0001 ? 'variance' : 'matched',
                        'notes'            => $line['notes'] ?? null,
                        'updated_at'       => now(),
                    ]);
            }

            return $this->repository->update($cycleCountId, ['status' => 'submitted']);
        });
    }

    /**
     * Approve a submitted cycle count.
     * Posts inventory adjustments for all lines with non-zero variance.
     * Moves status to 'approved'.
     */
    public function approve(string $cycleCountId): mixed
    {
        return DB::transaction(function () use ($cycleCountId) {
            $cycleCount = $this->repository->find($cycleCountId);
            if (! $cycleCount) {
                throw new CycleCountNotFoundException($cycleCountId);
            }

            $lines = DB::table('cycle_count_lines')
                ->where('cycle_count_id', $cycleCountId)
                ->where('status', 'variance')
                ->get();

            $varianceLines = 0;
            foreach ($lines as $line) {
                $variance = (float) $line->variance;
                if (abs($variance) < 0.0001) {
                    continue;
                }

                // Post an inventory movement for the variance
                $type = $variance > 0 ? 'adjustment_in' : 'adjustment_out';
                $absVariance = abs($variance);

                $item = $this->inventoryItemRepository->findByProductWarehouse(
                    $line->product_id,
                    $cycleCount->warehouse_id,
                    $line->variant_id ?? null,
                );

                $quantityBefore = $item ? (float) $item->quantity_on_hand : 0.0;
                $quantityAfter  = $quantityBefore + $variance;

                DB::table('inventory_movements')->insert([
                    'id'                => Str::uuid(),
                    'tenant_id'         => $cycleCount->tenant_id,
                    'product_id'        => $line->product_id,
                    'variant_id'        => $line->variant_id,
                    'warehouse_id'      => $cycleCount->warehouse_id,
                    'location_id'       => $cycleCount->location_id,
                    'type'              => $type,
                    'quantity'          => $absVariance,
                    'unit_cost'         => (float) ($item->average_cost ?? 0),
                    'total_cost'        => $absVariance * (float) ($item->average_cost ?? 0),
                    'quantity_before'   => $quantityBefore,
                    'quantity_after'    => $quantityAfter,
                    'reference_type'    => 'cycle_count',
                    'reference_id'      => $cycleCountId,
                    'notes'             => 'Cycle count variance adjustment',
                    'created_at'        => now(),
                    'updated_at'        => now(),
                ]);

                if ($item) {
                    $this->inventoryItemRepository->update($item->id, [
                        'quantity_on_hand'   => max(0.0, $quantityAfter),
                        'quantity_available' => max(0.0, $quantityAfter - (float) ($item->quantity_reserved ?? 0)),
                    ]);
                } else {
                    $this->inventoryItemRepository->create([
                        'tenant_id'          => $cycleCount->tenant_id,
                        'product_id'         => $line->product_id,
                        'variant_id'         => $line->variant_id,
                        'warehouse_id'       => $cycleCount->warehouse_id,
                        'location_id'        => $cycleCount->location_id,
                        'quantity_on_hand'   => max(0.0, $quantityAfter),
                        'quantity_available' => max(0.0, $quantityAfter),
                        'quantity_reserved'  => 0,
                        'average_cost'       => 0,
                    ]);
                }

                $varianceLines++;
            }

            $totalLines = DB::table('cycle_count_lines')
                ->where('cycle_count_id', $cycleCountId)
                ->count();

            $result = $this->repository->update($cycleCountId, [
                'status'       => 'approved',
                'completed_at' => now(),
            ]);

            $this->addEvent(new CycleCountCompleted(
                (int) $cycleCount->tenant_id,
                $cycleCountId,
                $totalLines,
                $varianceLines,
            ));
            $this->dispatchEvents();

            return $result;
        });
    }

    /**
     * Cancel a draft cycle count.
     */
    public function cancel(string $cycleCountId): mixed
    {
        return DB::transaction(function () use ($cycleCountId) {
            $cycleCount = $this->repository->find($cycleCountId);
            if (! $cycleCount) {
                throw new CycleCountNotFoundException($cycleCountId);
            }

            return $this->repository->update($cycleCountId, ['status' => 'cancelled']);
        });
    }
}
