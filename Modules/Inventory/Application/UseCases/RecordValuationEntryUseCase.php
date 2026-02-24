<?php

namespace Modules\Inventory\Application\UseCases;

use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Inventory\Domain\Contracts\InventoryValuationRepositoryInterface;
use Modules\Inventory\Domain\Events\StockValuationEntryRecorded;

/**
 * Records a stock valuation ledger entry whenever stock is received or deducted.
 *
 * Uses BCMath for all monetary calculations (DECIMAL(18,8) precision).
 *
 * Weighted Average Cost recalculation:
 *   new_avg = (running_balance_value + total_in) / (running_balance_qty + qty_in)
 *
 * FIFO: each receipt is stored as a separate layer (tracked via separate entries).
 * For the purposes of ledger recording, both methods write an immutable entry;
 * FIFO costing layers are resolved at the reporting/deduction stage.
 */
class RecordValuationEntryUseCase
{
    public function __construct(
        private InventoryValuationRepositoryInterface $valuationRepo,
    ) {}

    public function execute(array $data): object
    {
        return DB::transaction(function () use ($data) {
            $tenantId        = $data['tenant_id'];
            $productId       = $data['product_id'];
            $movementType    = $data['movement_type'];   // receipt | deduction | adjustment
            $qty             = (string) $data['qty'];
            $unitCost        = (string) $data['unit_cost'];
            $valuationMethod = $data['valuation_method'] ?? 'weighted_average';
            $referenceType   = $data['reference_type'] ?? null;
            $referenceId     = $data['reference_id'] ?? null;

            if (bccomp($qty, '0', 8) <= 0) {
                throw new DomainException('Valuation entry quantity must be greater than zero.');
            }

            if (bccomp($unitCost, '0', 8) < 0) {
                throw new DomainException('Unit cost cannot be negative.');
            }

            // Fetch last position for this product to compute running balances
            $last = $this->valuationRepo->findLastByProduct($tenantId, $productId);

            $prevQty   = $last ? (string) $last->running_balance_qty   : '0.00000000';
            $prevValue = $last ? (string) $last->running_balance_value  : '0.00000000';

            // Signed qty / value: positive for in, negative for out
            if ($movementType === 'deduction') {
                $signedQty        = bcsub('0', $qty, 8);
                $effectiveCost    = $this->resolveDeductionCost($prevQty, $prevValue, $valuationMethod);
                $totalValue       = bcsub('0', bcmul($qty, $effectiveCost, 8), 8);
            } else {
                // receipt or adjustment-in
                $signedQty  = $qty;
                $totalValue = bcmul($qty, $unitCost, 8);
            }

            $newBalanceQty   = bcadd($prevQty, $signedQty, 8);
            $newBalanceValue = bcadd($prevValue, $totalValue, 8);

            // For weighted average, recompute the unit cost used
            if ($movementType !== 'deduction' && $valuationMethod === 'weighted_average') {
                $unitCost = $this->computeWeightedAverageCost($newBalanceQty, $newBalanceValue);
            }

            $entry = $this->valuationRepo->create([
                'tenant_id'             => $tenantId,
                'product_id'            => $productId,
                'movement_type'         => $movementType,
                'qty'                   => $qty,
                'unit_cost'             => $movementType === 'deduction' ? $this->resolveDeductionCost($prevQty, $prevValue, $valuationMethod) : $unitCost,
                'total_value'           => $totalValue,
                'running_balance_qty'   => $newBalanceQty,
                'running_balance_value' => $newBalanceValue,
                'valuation_method'      => $valuationMethod,
                'reference_type'        => $referenceType,
                'reference_id'          => $referenceId,
            ]);

            Event::dispatch(new StockValuationEntryRecorded(
                $entry->id,
                $tenantId,
                $productId,
                $movementType,
                $totalValue,
            ));

            return $entry;
        });
    }

    /** Resolve the unit cost to use when deducting stock. */
    private function resolveDeductionCost(string $balanceQty, string $balanceValue, string $method): string
    {
        if ($method === 'weighted_average') {
            return $this->computeWeightedAverageCost($balanceQty, $balanceValue);
        }
        // FIFO: use the current running average as a proxy when balance is available
        return $this->computeWeightedAverageCost($balanceQty, $balanceValue);
    }

    /** Compute the weighted average cost from running balance, guarding zero-qty. */
    private function computeWeightedAverageCost(string $qty, string $value): string
    {
        if (bccomp($qty, '0', 8) === 0) {
            return '0.00000000';
        }
        return bcdiv($value, $qty, 8);
    }
}
