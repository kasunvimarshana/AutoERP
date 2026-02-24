<?php
namespace Modules\Inventory\Application\UseCases;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Inventory\Application\Services\StockLevelService;
use Modules\Inventory\Domain\Contracts\StockMovementRepositoryInterface;
use Modules\Inventory\Domain\Events\StockDeducted;
class DeductStockUseCase
{
    public function __construct(
        private StockMovementRepositoryInterface $movementRepo,
        private StockLevelService $stockLevelService,
    ) {}
    public function execute(array $data): object
    {
        return DB::transaction(function () use ($data) {
            $tenantId = auth()->user()?->tenant_id ?? $data['tenant_id'];
            $this->stockLevelService->decrease(
                $data['product_id'],
                $data['from_location_id'],
                (string)$data['qty'],
                $tenantId,
                $data['variant_id'] ?? null,
            );
            $movement = $this->movementRepo->create(array_merge($data, [
                'type' => 'delivery',
                'posted_at' => now(),
                'posted_by' => auth()->id(),
            ]));
            Event::dispatch(new StockDeducted($data['product_id'], (string)$data['qty'], $data['from_location_id']));
            return $movement;
        });
    }
}
