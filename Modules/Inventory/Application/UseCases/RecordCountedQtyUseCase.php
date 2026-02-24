<?php

namespace Modules\Inventory\Application\UseCases;

use DomainException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Inventory\Domain\Contracts\CycleCountRepositoryInterface;
use Modules\Shared\Domain\Contracts\UseCaseInterface;

/**
 * Records the physically counted quantity for a product line on a cycle count.
 *
 * If a line for the given product already exists it is updated; otherwise a new
 * line is created. An optional expected_qty may be supplied by the caller;
 * when omitted it defaults to zero and can be corrected before posting.
 */
class RecordCountedQtyUseCase implements UseCaseInterface
{
    public function __construct(
        private CycleCountRepositoryInterface $cycleCountRepo,
    ) {}

    public function execute(array $data): mixed
    {
        $cycleCountId = $data['cycle_count_id'];
        $productId    = $data['product_id'];
        $countedQty   = (string) ($data['counted_qty'] ?? '0');
        $expectedQty  = (string) ($data['expected_qty'] ?? '0');

        if (bccomp($countedQty, '0', 8) < 0) {
            throw new DomainException('Counted quantity cannot be negative.');
        }

        $cycleCount = $this->cycleCountRepo->findById($cycleCountId);
        if (! $cycleCount) {
            throw new ModelNotFoundException('Cycle count not found.');
        }

        if (! in_array($cycleCount->status, ['draft', 'in_progress'], true)) {
            throw new DomainException('Counted quantities can only be recorded on draft or in-progress cycle counts.');
        }

        return DB::transaction(function () use ($cycleCount, $productId, $countedQty, $expectedQty, $data) {
            $countedNorm  = bcadd($countedQty, '0.00000000', 8);
            $expectedNorm = bcadd($expectedQty, '0.00000000', 8);

            // Check whether a line for this product already exists
            $lines        = $this->cycleCountRepo->linesForCount($cycleCount->id);
            $existingLine = null;
            foreach ($lines as $line) {
                if ($line->product_id === $productId) {
                    $existingLine = $line;
                    break;
                }
            }

            if ($existingLine) {
                $line = $this->cycleCountRepo->updateLine($existingLine->id, [
                    'counted_qty'  => $countedNorm,
                    'expected_qty' => $expectedNorm,
                ]);
            } else {
                $line = $this->cycleCountRepo->createLine([
                    'id'             => (string) Str::uuid(),
                    'cycle_count_id' => $cycleCount->id,
                    'tenant_id'      => $cycleCount->tenant_id,
                    'product_id'     => $productId,
                    'expected_qty'   => $expectedNorm,
                    'counted_qty'    => $countedNorm,
                    'notes'          => $data['notes'] ?? null,
                ]);
            }

            // Promote status to in_progress once the first line is recorded
            if ($cycleCount->status === 'draft') {
                $this->cycleCountRepo->update($cycleCount->id, ['status' => 'in_progress']);
            }

            return $line;
        });
    }
}
