<?php
namespace App\Repositories;
use App\Domain\Contracts\StockRepositoryInterface;
use App\Domain\Models\StockLevel;
use App\Domain\Models\StockReservation;
use Illuminate\Support\Facades\DB;

class StockRepository extends BaseRepository implements StockRepositoryInterface
{
    private StockReservation $reservationModel;

    public function __construct(StockLevel $model, StockReservation $reservationModel)
    {
        parent::__construct($model);
        $this->reservationModel = $reservationModel;
    }

    public function getStockLevel(string $tenantId, string $productId, string $warehouseId): ?object
    {
        return $this->model->byTenant($tenantId)->forProduct($productId)->forWarehouse($warehouseId)->first();
    }

    public function getOrCreateStockLevel(string $tenantId, string $productId, string $warehouseId): object
    {
        return $this->model->firstOrCreate(
            ['tenant_id' => $tenantId, 'product_id' => $productId, 'warehouse_id' => $warehouseId],
            ['quantity_available' => 0, 'quantity_reserved' => 0, 'quantity_on_hand' => 0, 'version' => 1]
        );
    }

    public function getTotalStock(string $tenantId, string $productId): array
    {
        $rows = $this->model->byTenant($tenantId)->forProduct($productId)->get();
        return [
            'total_available' => (float) $rows->sum('quantity_available'),
            'total_reserved'  => (float) $rows->sum('quantity_reserved'),
            'total_on_hand'   => (float) $rows->sum('quantity_on_hand'),
        ];
    }

    public function updateStockLevel(string $tenantId, string $productId, string $warehouseId, array $data): object
    {
        $level = $this->getOrCreateStockLevel($tenantId, $productId, $warehouseId);
        $level->update($data);
        return $level->fresh();
    }

    public function lockStockLevelForUpdate(string $tenantId, string $productId, string $warehouseId): ?object
    {
        return $this->model->byTenant($tenantId)->forProduct($productId)->forWarehouse($warehouseId)->lockForUpdate()->first();
    }

    public function reserveStock(string $tenantId, string $productId, string $warehouseId, int $quantity, array $reservationData): object
    {
        return DB::transaction(function () use ($tenantId, $productId, $warehouseId, $quantity, $reservationData) {
            $level = $this->lockStockLevelForUpdate($tenantId, $productId, $warehouseId)
                ?? $this->getOrCreateStockLevel($tenantId, $productId, $warehouseId);
            if ($level->quantity_available < $quantity) {
                throw new \RuntimeException("Insufficient stock. Available: {$level->quantity_available}, Requested: {$quantity}.");
            }
            $level->quantity_available -= $quantity;
            $level->quantity_reserved  += $quantity;
            $level->incrementVersion();
            $level->save();
            return $this->reservationModel->create(array_merge($reservationData, [
                'tenant_id' => $tenantId, 'product_id' => $productId,
                'warehouse_id' => $warehouseId, 'quantity' => $quantity,
                'status' => StockReservation::STATUS_PENDING,
            ]));
        });
    }

    public function commitReservation(string $tenantId, string $reservationId, ?string $performedBy = null): array
    {
        return DB::transaction(function () use ($tenantId, $reservationId, $performedBy) {
            $reservation = $this->findReservationById($tenantId, $reservationId);
            if (!$reservation || !$reservation->canBeCommitted()) throw new \RuntimeException("Cannot commit reservation.");
            $level = $this->lockStockLevelForUpdate($tenantId, $reservation->product_id, $reservation->warehouse_id);
            $level->quantity_reserved = max(0, (float)$level->quantity_reserved - $reservation->quantity);
            $level->quantity_on_hand  = max(0, (float)$level->quantity_on_hand  - $reservation->quantity);
            $level->incrementVersion(); $level->save();
            $reservation->update(['status' => StockReservation::STATUS_COMMITTED, 'committed_at' => now(), 'committed_by' => $performedBy]);
            return ['stock_level' => $level, 'reservation' => $reservation];
        });
    }

    public function releaseReservation(string $tenantId, string $reservationId, ?string $performedBy = null): array
    {
        return DB::transaction(function () use ($tenantId, $reservationId, $performedBy) {
            $reservation = $this->findReservationById($tenantId, $reservationId);
            if (!$reservation || !$reservation->canBeReleased()) throw new \RuntimeException("Cannot release reservation.");
            $level = $this->lockStockLevelForUpdate($tenantId, $reservation->product_id, $reservation->warehouse_id);
            $level->quantity_available += $reservation->quantity;
            $level->quantity_reserved   = max(0, (float)$level->quantity_reserved - $reservation->quantity);
            $level->incrementVersion(); $level->save();
            $reservation->update(['status' => StockReservation::STATUS_RELEASED, 'released_at' => now(), 'released_by' => $performedBy]);
            return ['stock_level' => $level, 'reservation' => $reservation];
        });
    }

    public function getExpiredReservations(): mixed
    {
        return $this->reservationModel->expired()->get();
    }

    public function findReservationById(string $tenantId, string $reservationId): ?object
    {
        return $this->reservationModel->byTenant($tenantId)->find($reservationId);
    }

    public function getStockByWarehouse(string $tenantId, string $warehouseId): mixed
    {
        return $this->model->byTenant($tenantId)->forWarehouse($warehouseId)->with('product')->get();
    }

    public function getStockByProduct(string $tenantId, string $productId): mixed
    {
        return $this->model->byTenant($tenantId)->forProduct($productId)->with('warehouse')->get();
    }
}
