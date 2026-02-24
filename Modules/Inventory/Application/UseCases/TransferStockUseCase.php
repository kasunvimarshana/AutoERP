<?php
namespace Modules\Inventory\Application\UseCases;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Inventory\Application\Services\StockLevelService;
use Modules\Inventory\Domain\Contracts\StockMovementRepositoryInterface;
use Modules\Inventory\Domain\Events\StockTransferred;
class TransferStockUseCase
{
    public function __construct(
        private StockMovementRepositoryInterface $movementRepo,
        private StockLevelService $stockLevelService,
    ) {}
    public function execute(array $data): object
    {
        return DB::transaction(function () use ($data) {
            $tenantId = auth()->user()?->tenant_id ?? $data['tenant_id'];
            $variantId = $data['variant_id'] ?? null;
            $qty = (string)$data['qty'];
            $this->stockLevelService->decrease($data['product_id'], $data['from_location_id'], $qty, $tenantId, $variantId);
            $this->stockLevelService->increase($data['product_id'], $data['to_location_id'], $qty, $tenantId, $variantId);
            $movement = $this->movementRepo->create(array_merge($data, [
                'type' => 'transfer',
                'posted_at' => now(),
                'posted_by' => auth()->id(),
            ]));
            Event::dispatch(new StockTransferred($data['product_id'], $data['from_location_id'], $data['to_location_id'], $qty));
            return $movement;
        });
    }
}
