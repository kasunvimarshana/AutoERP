<?php
namespace Modules\Inventory\Application\UseCases;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Inventory\Application\Services\StockLevelService;
use Modules\Inventory\Domain\Contracts\StockLevelRepositoryInterface;
use Modules\Inventory\Domain\Contracts\StockMovementRepositoryInterface;
use Modules\Inventory\Domain\Events\StockAdjusted;
class AdjustStockUseCase
{
    public function __construct(
        private StockMovementRepositoryInterface $movementRepo,
        private StockLevelRepositoryInterface $levelRepo,
        private StockLevelService $stockLevelService,
    ) {}
    public function execute(array $data): object
    {
        return DB::transaction(function () use ($data) {
            $tenantId = auth()->user()?->tenant_id ?? $data['tenant_id'];
            $productId = $data['product_id'];
            $locationId = $data['location_id'];
            $variantId = $data['variant_id'] ?? null;
            $newQty = (string)$data['qty'];
            $reason = $data['reason'] ?? 'Manual adjustment';
            $current = $this->levelRepo->getStockLevel($productId, $locationId, $variantId);
            $currentQty = $current ? $current->qty : '0.00000000';
            $diff = bcsub($newQty, $currentQty, 8);
            if (bccomp($diff, '0', 8) > 0) {
                $this->stockLevelService->increase($productId, $locationId, $diff, $tenantId, $variantId);
            } elseif (bccomp($diff, '0', 8) < 0) {
                $this->stockLevelService->decrease($productId, $locationId, bcmul($diff, '-1', 8), $tenantId, $variantId);
            }
            $movement = $this->movementRepo->create([
                'tenant_id' => $tenantId,
                'type' => 'adjustment',
                'product_id' => $productId,
                'variant_id' => $variantId,
                'to_location_id' => $locationId,
                'qty' => $diff,
                'unit_cost' => $data['unit_cost'] ?? '0.00000000',
                'notes' => $reason,
                'posted_at' => now(),
                'posted_by' => auth()->id(),
            ]);
            Event::dispatch(new StockAdjusted($productId, $locationId, $diff, $reason));
            return $movement;
        });
    }
}
