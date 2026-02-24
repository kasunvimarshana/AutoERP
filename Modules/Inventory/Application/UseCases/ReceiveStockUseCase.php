<?php
namespace Modules\Inventory\Application\UseCases;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Inventory\Application\Services\StockLevelService;
use Modules\Inventory\Domain\Contracts\StockMovementRepositoryInterface;
use Modules\Inventory\Domain\Events\StockReceived;
class ReceiveStockUseCase
{
    public function __construct(
        private StockMovementRepositoryInterface $movementRepo,
        private StockLevelService $stockLevelService,
    ) {}
    public function execute(array $data): object
    {
        return DB::transaction(function () use ($data) {
            $movement = $this->movementRepo->create(array_merge($data, [
                'type' => 'receipt',
                'posted_at' => now(),
                'posted_by' => auth()->id(),
            ]));
            $tenantId = auth()->user()?->tenant_id ?? $data['tenant_id'];
            $this->stockLevelService->increase(
                $data['product_id'],
                $data['to_location_id'],
                (string)$data['qty'],
                $tenantId,
                $data['variant_id'] ?? null,
            );
            Event::dispatch(new StockReceived($data['product_id'], (string)$data['qty'], $data['to_location_id']));
            return $movement;
        });
    }
}
