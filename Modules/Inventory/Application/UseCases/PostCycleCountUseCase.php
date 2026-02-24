<?php

namespace Modules\Inventory\Application\UseCases;

use DomainException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Modules\Inventory\Domain\Contracts\CycleCountRepositoryInterface;
use Modules\Inventory\Domain\Contracts\StockMovementRepositoryInterface;
use Modules\Inventory\Domain\Events\CycleCountPosted;
use Modules\Shared\Domain\Contracts\UseCaseInterface;

/**
 * Posts a cycle count by reconciling counted vs expected quantities.
 *
 * For every count line where counted_qty ≠ expected_qty, an adjustment
 * stock movement is created so the stock ledger reflects the physical
 * count. Lines with zero variance are skipped. The cycle count is
 * transitioned to `posted` status after all adjustments are persisted.
 */
class PostCycleCountUseCase implements UseCaseInterface
{
    public function __construct(
        private CycleCountRepositoryInterface  $cycleCountRepo,
        private StockMovementRepositoryInterface $movementRepo,
    ) {}

    public function execute(array $data): mixed
    {
        $cycleCountId = $data['cycle_count_id'];
        $postedBy     = $data['posted_by'] ?? null;

        $cycleCount = $this->cycleCountRepo->findById($cycleCountId);
        if (! $cycleCount) {
            throw new ModelNotFoundException('Cycle count not found.');
        }

        if ($cycleCount->status === 'posted') {
            throw new DomainException('Cycle count has already been posted.');
        }

        if ($cycleCount->status === 'cancelled') {
            throw new DomainException('A cancelled cycle count cannot be posted.');
        }

        $lines = $this->cycleCountRepo->linesForCount($cycleCountId);
        if (empty($lines)) {
            throw new DomainException('Cannot post a cycle count with no counted lines.');
        }

        return DB::transaction(function () use ($cycleCount, $lines, $postedBy) {
            $adjustedCount = 0;
            $postedAt      = now()->toDateTimeString();

            foreach ($lines as $line) {
                $variance = bcsub(
                    bcadd((string) $line->counted_qty, '0.00000000', 8),
                    bcadd((string) $line->expected_qty, '0.00000000', 8),
                    8,
                );

                // Skip lines with zero variance
                if (bccomp($variance, '0.00000000', 8) === 0) {
                    continue;
                }

                // Positive variance → receipt adjustment; negative → deduction adjustment
                $absVariance = bccomp($variance, '0', 8) >= 0
                    ? $variance
                    : bcmul($variance, '-1', 8);

                $movementType = bccomp($variance, '0', 8) >= 0 ? 'adjustment_in' : 'adjustment_out';

                $this->movementRepo->create([
                    'id'             => (string) Str::uuid(),
                    'tenant_id'      => $cycleCount->tenant_id,
                    'type'           => $movementType,
                    'product_id'     => $line->product_id,
                    'to_location_id' => $cycleCount->location_id,
                    'qty'            => $absVariance,
                    'unit_cost'      => '0.00000000',
                    'reference_type' => 'cycle_count',
                    'reference_id'   => $cycleCount->id,
                    'notes'          => "Cycle count reconciliation: {$cycleCount->reference}",
                    'posted_by'      => $postedBy,
                    'posted_at'      => $postedAt,
                    'created_by'     => $postedBy,
                ]);

                $adjustedCount++;
            }

            $this->cycleCountRepo->update($cycleCount->id, ['status' => 'posted']);

            Event::dispatch(new CycleCountPosted(
                cycleCountId:  $cycleCount->id,
                tenantId:      $cycleCount->tenant_id,
                warehouseId:   $cycleCount->warehouse_id,
                linesAdjusted: $adjustedCount,
            ));

            return $this->cycleCountRepo->findById($cycleCount->id);
        });
    }
}
