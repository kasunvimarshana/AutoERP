<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Core\Application\Contracts\CurrentUserContextAccessorInterface;
use Modules\Inventory\Application\Support\InventoryServiceSupport;

final class StockReservationService
{
    public function __construct(
        private readonly InventoryServiceSupport $support,
        private readonly StockAvailabilityService $availability,
        private readonly InventoryTransactionService $transactions,
        private readonly CurrentUserContextAccessorInterface $currentUser,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     * @return array{reservations: array<int, array<string, mixed>>}
     */
    public function reserve(array $payload): array
    {
        return DB::transaction(function () use ($payload): array {
            $tenantId = $this->support->tenantId($payload);
            $organizationUnitId = $this->support->organizationUnitId($payload);
            $lines = $this->support->normalizeLines($payload);
            $this->support->validateReferences($tenantId, $lines);
            $baseUoms = $this->support->itemBaseUomMap($tenantId, $lines);
            $reservations = [];

            foreach ($lines as $line) {
                $baseUomId = $baseUoms[(int) $line['item_id']];
                $baseQuantity = $this->support->convertToBase($tenantId, (int) $line['item_id'], (int) $line['uom_id'], $baseUomId, (float) $line['quantity']);
                $available = $this->availability->check($this->support->stockCriteriaFromLine($tenantId, $line));
                if ($baseQuantity > $available['available_quantity'] + 0.0001) {
                    throw ValidationException::withMessages(['stock' => ['Insufficient stock available to reserve.']]);
                }

                $this->support->adjustStockLevel($tenantId, $organizationUnitId, $line, $baseUomId, 0, 'IN', $baseQuantity);
                $reservationId = DB::table('stock_reservations')->insertGetId([
                    'tenant_id' => $tenantId,
                    'organization_unit_id' => $organizationUnitId,
                    'item_id' => (int) $line['item_id'],
                    'variant_id' => $line['variant_id'] ?? null,
                    'batch_id' => $line['batch_id'] ?? null,
                    'serial_id' => $line['serial_id'] ?? null,
                    'warehouse_id' => (int) $line['warehouse_id'],
                    'location_id' => $line['location_id'] ?? null,
                    'transaction_uom_id' => (int) $line['uom_id'],
                    'base_uom_id' => $baseUomId,
                    'quantity' => (float) $line['quantity'],
                    'base_quantity' => $baseQuantity,
                    'status' => 'ACTIVE',
                    'reserved_for_type' => $payload['reserved_for_type'] ?? $payload['source_type'] ?? null,
                    'reserved_for_id' => $payload['reserved_for_id'] ?? $payload['source_id'] ?? null,
                    'expires_at' => $line['expires_at'] ?? $payload['expires_at'] ?? null,
                    'unit_cost' => $line['unit_cost'] ?? null,
                    'reserved_by' => $payload['reserved_by'] ?? $this->currentUser->currentUserId(),
                    'notes' => $line['notes'] ?? $payload['notes'] ?? null,
                    'row_version' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $this->transactions->record($this->reservationMovement($payload, $line, $tenantId, $organizationUnitId, $baseUomId, $baseQuantity, 'RESERVATION', 'OUT'));
                $reservations[] = ['reservation_id' => $reservationId, 'base_quantity' => $baseQuantity];
            }

            return ['reservations' => $reservations];
        });
    }

    public function release(int $tenantId, int $reservationId, ?float $quantity = null): array
    {
        return DB::transaction(function () use ($tenantId, $reservationId, $quantity): array {
            $reservation = $this->reservation($tenantId, $reservationId);
            $openQuantity = (float) $reservation->base_quantity - (float) $reservation->quantity_consumed - (float) $reservation->quantity_released;
            $releaseQuantity = $quantity === null ? $openQuantity : $quantity;
            if ($releaseQuantity <= 0 || $releaseQuantity > $openQuantity + 0.0001) {
                throw ValidationException::withMessages(['quantity' => ['Invalid reservation release quantity.']]);
            }

            $line = $this->lineFromReservation($reservation);
            $this->support->adjustStockLevel($tenantId, $reservation->organization_unit_id === null ? null : (int) $reservation->organization_unit_id, $line, (int) $reservation->base_uom_id, 0, 'IN', -$releaseQuantity);
            $this->transactions->record($this->releaseMovement($reservation, $releaseQuantity));
            $newReleased = (float) $reservation->quantity_released + $releaseQuantity;
            $status = $newReleased + (float) $reservation->quantity_consumed >= (float) $reservation->base_quantity - 0.0001 ? 'RELEASED' : $reservation->status;
            DB::table('stock_reservations')->where('id', $reservationId)->update([
                'quantity_released' => $this->support->roundQuantity($newReleased),
                'status' => $status,
                'released_by' => $this->currentUser->currentUserId(),
                'released_at' => now(),
                'row_version' => ((int) $reservation->row_version) + 1,
                'updated_at' => now(),
            ]);

            return ['reservation_id' => $reservationId, 'released_quantity' => $this->support->roundQuantity($releaseQuantity)];
        });
    }

    public function consume(int $tenantId, int $reservationId, float $quantity): array
    {
        return DB::transaction(function () use ($tenantId, $reservationId, $quantity): array {
            $reservation = $this->reservation($tenantId, $reservationId);
            $openQuantity = (float) $reservation->base_quantity - (float) $reservation->quantity_consumed - (float) $reservation->quantity_released;
            if ($quantity <= 0 || $quantity > $openQuantity + 0.0001) {
                throw ValidationException::withMessages(['quantity' => ['Invalid reservation consume quantity.']]);
            }

            $line = $this->lineFromReservation($reservation);
            $this->support->adjustStockLevel($tenantId, $reservation->organization_unit_id === null ? null : (int) $reservation->organization_unit_id, $line, (int) $reservation->base_uom_id, 0, 'IN', -$quantity);
            $this->transactions->record($this->releaseMovement($reservation, $quantity));
            $newConsumed = (float) $reservation->quantity_consumed + $quantity;
            $status = $newConsumed + (float) $reservation->quantity_released >= (float) $reservation->base_quantity - 0.0001 ? 'CONSUMED' : 'PARTIALLY_CONSUMED';
            DB::table('stock_reservations')->where('id', $reservationId)->update([
                'quantity_consumed' => $this->support->roundQuantity($newConsumed),
                'status' => $status,
                'consumed_by' => $this->currentUser->currentUserId(),
                'consumed_at' => now(),
                'row_version' => ((int) $reservation->row_version) + 1,
                'updated_at' => now(),
            ]);

            return ['reservation_id' => $reservationId, 'consumed_quantity' => $this->support->roundQuantity($quantity)];
        });
    }

    private function reservation(int $tenantId, int $reservationId): object
    {
        $reservation = DB::table('stock_reservations')
            ->where('tenant_id', $tenantId)
            ->where('id', $reservationId)
            ->lockForUpdate()
            ->first();

        if ($reservation === null) {
            throw ValidationException::withMessages(['reservation_id' => ['Reservation not found.']]);
        }

        return $reservation;
    }

    private function lineFromReservation(object $reservation): array
    {
        return [
            'item_id' => (int) $reservation->item_id,
            'variant_id' => $reservation->variant_id,
            'batch_id' => $reservation->batch_id,
            'serial_id' => $reservation->serial_id,
            'warehouse_id' => (int) $reservation->warehouse_id,
            'location_id' => $reservation->location_id,
            'uom_id' => (int) $reservation->transaction_uom_id,
            'quantity' => (float) $reservation->quantity,
        ];
    }

    private function reservationMovement(array $payload, array $line, int $tenantId, ?int $organizationUnitId, int $baseUomId, float $baseQuantity, string $type, string $direction): array
    {
        return [
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'direction' => $direction,
            'movement_type' => $type,
            'item_id' => (int) $line['item_id'],
            'variant_id' => $line['variant_id'] ?? null,
            'batch_id' => $line['batch_id'] ?? null,
            'serial_id' => $line['serial_id'] ?? null,
            'warehouse_id' => $line['warehouse_id'] ?? null,
            'location_id' => $line['location_id'] ?? null,
            'source_type' => $payload['source_type'] ?? $payload['reserved_for_type'] ?? null,
            'source_id' => $payload['source_id'] ?? $payload['reserved_for_id'] ?? null,
            'source_line_id' => $line['source_line_id'] ?? null,
            'transaction_uom_id' => (int) $line['uom_id'],
            'base_uom_id' => $baseUomId,
            'quantity' => (float) $line['quantity'],
            'base_quantity' => $baseQuantity,
            'notes' => $line['notes'] ?? $payload['notes'] ?? null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function releaseMovement(object $reservation, float $baseQuantity): array
    {
        $quantity = (float) $reservation->base_quantity > 0
            ? ((float) $reservation->quantity * $baseQuantity / (float) $reservation->base_quantity)
            : $baseQuantity;

        return [
            'tenant_id' => (int) $reservation->tenant_id,
            'organization_unit_id' => $reservation->organization_unit_id,
            'direction' => 'IN',
            'movement_type' => 'RESERVATION_RELEASE',
            'item_id' => (int) $reservation->item_id,
            'variant_id' => $reservation->variant_id,
            'batch_id' => $reservation->batch_id,
            'serial_id' => $reservation->serial_id,
            'warehouse_id' => (int) $reservation->warehouse_id,
            'location_id' => $reservation->location_id,
            'source_type' => $reservation->reserved_for_type,
            'source_id' => $reservation->reserved_for_id,
            'transaction_uom_id' => (int) $reservation->transaction_uom_id,
            'base_uom_id' => (int) $reservation->base_uom_id,
            'quantity' => $this->support->roundQuantity($quantity),
            'base_quantity' => $this->support->roundQuantity($baseQuantity),
            'notes' => $reservation->notes,
        ];
    }
}
