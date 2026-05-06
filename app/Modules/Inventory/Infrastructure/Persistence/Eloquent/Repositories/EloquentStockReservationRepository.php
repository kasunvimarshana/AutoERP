<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Inventory\Domain\Entities\StockReservation;
use Modules\Inventory\Domain\Exceptions\InsufficientAvailableStockException;
use Modules\Inventory\Domain\RepositoryInterfaces\StockReservationRepositoryInterface;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\StockReservationModel;

class EloquentStockReservationRepository implements StockReservationRepositoryInterface
{
    public function __construct(private readonly StockReservationModel $stockReservationModel) {}

    public function create(StockReservation $reservation): StockReservation
    {
        return DB::transaction(function () use ($reservation): StockReservation {
            /** @var StockReservationModel $saved */
            $saved = $this->stockReservationModel->newQuery()->create([
                'tenant_id' => $reservation->getTenantId(),
                'product_id' => $reservation->getProductId(),
                'variant_id' => $reservation->getVariantId(),
                'batch_id' => $reservation->getBatchId(),
                'serial_id' => $reservation->getSerialId(),
                'location_id' => $reservation->getLocationId(),
                'quantity' => $reservation->getQuantity(),
                'reserved_for_type' => $reservation->getReservedForType(),
                'reserved_for_id' => $reservation->getReservedForId(),
                'expires_at' => $reservation->getExpiresAt(),
            ]);

            $this->applyReservedDelta($reservation, '+');

            return $this->mapToEntity($saved);
        });
    }

    public function findById(int $tenantId, int $reservationId): ?StockReservation
    {
        /** @var StockReservationModel|null $model */
        $model = $this->stockReservationModel->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('id', $reservationId)
            ->first();

        return $model !== null ? $this->mapToEntity($model) : null;
    }

    public function paginate(int $tenantId, int $perPage, int $page): mixed
    {
        return $this->stockReservationModel->newQuery()
            ->where('tenant_id', $tenantId)
            ->orderByDesc('id')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    public function delete(int $tenantId, int $reservationId): bool
    {
        return DB::transaction(function () use ($tenantId, $reservationId): bool {
            /** @var StockReservationModel|null $reservation */
            $reservation = $this->stockReservationModel->newQuery()
                ->where('tenant_id', $tenantId)
                ->where('id', $reservationId)
                ->lockForUpdate()
                ->first();

            if ($reservation === null) {
                return false;
            }

            $entity = $this->mapToEntity($reservation);
            $this->applyReservedDelta($entity, '-');

            return (bool) $reservation->delete();
        });
    }

    public function deleteExpired(int $tenantId, ?string $expiresBefore = null): int
    {
        return DB::transaction(function () use ($tenantId, $expiresBefore): int {
            $cutoff = $expiresBefore !== null ? Carbon::parse($expiresBefore) : now();

            /** @var Collection<int, StockReservationModel> $reservations */
            $reservations = $this->stockReservationModel->newQuery()
                ->where('tenant_id', $tenantId)
                ->whereNotNull('expires_at')
                ->where('expires_at', '<=', $cutoff)
                ->lockForUpdate()
                ->get();

            if ($reservations->isEmpty()) {
                return 0;
            }

            foreach ($reservations as $reservation) {
                $this->applyReservedDelta($this->mapToEntity($reservation), '-');
            }

            return $this->stockReservationModel->newQuery()
                ->where('tenant_id', $tenantId)
                ->whereNotNull('expires_at')
                ->where('expires_at', '<=', $cutoff)
                ->delete();
        });
    }

    private function applyReservedDelta(StockReservation $reservation, string $op): void
    {
        $row = DB::table('stock_levels')
            ->where('tenant_id', $reservation->getTenantId())
            ->where('product_id', $reservation->getProductId())
            ->where('variant_id', $reservation->getVariantId())
            ->where('batch_id', $reservation->getBatchId())
            ->where('serial_id', $reservation->getSerialId())
            ->where('location_id', $reservation->getLocationId())
            ->lockForUpdate()
            ->first();

        if ($row === null) {
            throw new \RuntimeException('Stock level row not found for reservation context.');
        }

        $currentReserved = (string) $row->quantity_reserved;
        $currentOnHand = (string) $row->quantity_on_hand;
        $quantity = $reservation->getQuantity();

        if ($op === '+') {
            $availableQty = bcsub($currentOnHand, $currentReserved, 6);
            if (bccomp($quantity, $availableQty, 6) === 1) {
                throw new InsufficientAvailableStockException;
            }
        }

        $newReserved = $op === '+' ? bcadd($currentReserved, $quantity, 6) : bcsub($currentReserved, $quantity, 6);

        if (bccomp($newReserved, '0', 6) < 0) {
            throw new \RuntimeException('Reserved quantity cannot be negative.');
        }

        DB::table('stock_levels')
            ->where('id', $row->id)
            ->update([
                'quantity_reserved' => $newReserved,
                'updated_at' => now(),
            ]);
    }

    private function mapToEntity(StockReservationModel $model): StockReservation
    {
        return new StockReservation(
            tenantId: (int) $model->tenant_id,
            productId: (int) $model->product_id,
            variantId: $model->variant_id !== null ? (int) $model->variant_id : null,
            batchId: $model->batch_id !== null ? (int) $model->batch_id : null,
            serialId: $model->serial_id !== null ? (int) $model->serial_id : null,
            locationId: (int) $model->location_id,
            quantity: (string) $model->quantity,
            reservedForType: $model->reserved_for_type,
            reservedForId: $model->reserved_for_id !== null ? (int) $model->reserved_for_id : null,
            expiresAt: $model->expires_at?->format('Y-m-d H:i:s'),
            id: (int) $model->id,
        );
    }
}
