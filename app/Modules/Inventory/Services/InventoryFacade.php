<?php

declare(strict_types=1);

namespace Modules\Inventory\Services;

use InvalidArgumentException;
use Modules\Inventory\DTOs\AllocationData;
use Modules\Inventory\DTOs\ReservationData;
use Modules\Inventory\DTOs\StockAdjustmentData;
use Modules\Inventory\DTOs\StockMovementData;
use Modules\Inventory\DTOs\StockTransferData;
use Modules\Inventory\Enums\InventoryDirection;
use Modules\Inventory\Models\InventoryAdjustment;
use Modules\Inventory\Models\InventoryAllocation;
use Modules\Inventory\Models\InventoryMovement;
use Modules\Inventory\Models\InventoryReservation;
use Modules\Inventory\Models\InventoryTransfer;

final class InventoryFacade
{
    public function __construct(
        private readonly StockMovementService $movements,
        private readonly InventoryAllocationService $allocations,
        private readonly StockReservationService $reservations,
        private readonly StockAdjustmentService $adjustments,
        private readonly StockTransferService $transfers,
    ) {}

    public function receive(StockMovementData $data, ?int $postedBy = null): InventoryMovement
    {
        if ($data->direction !== InventoryDirection::In) {
            throw new InvalidArgumentException('Inventory receive requires an inbound movement.');
        }

        return $this->movements->record($data, $postedBy);
    }

    public function issue(StockMovementData $data, ?int $postedBy = null): InventoryMovement
    {
        if ($data->direction !== InventoryDirection::Out) {
            throw new InvalidArgumentException('Inventory issue requires an outbound movement.');
        }

        return $this->movements->record($data, $postedBy);
    }

    public function allocate(AllocationData $data): InventoryAllocation
    {
        return $this->allocations->allocate($data);
    }

    public function issueAllocation(InventoryAllocation $allocation, ?string $quantity = null, ?int $issuedBy = null): InventoryAllocation
    {
        return $this->allocations->issue($allocation, $quantity, $issuedBy);
    }

    public function release(InventoryAllocation $allocation, ?string $quantity = null, ?int $releasedBy = null): InventoryAllocation
    {
        return $this->allocations->release($allocation, $quantity, $releasedBy);
    }

    public function reserve(ReservationData $data): InventoryReservation
    {
        return $this->reservations->reserve($data);
    }

    public function unreserve(InventoryReservation $reservation, ?string $quantity = null, ?int $releasedBy = null): InventoryReservation
    {
        return $this->reservations->release($reservation, $quantity, $releasedBy);
    }

    public function transfer(StockTransferData $data): InventoryTransfer
    {
        return $this->transfers->create($data);
    }

    public function postTransfer(InventoryTransfer $transfer, ?int $postedBy = null): InventoryTransfer
    {
        return $this->transfers->post($transfer, $postedBy);
    }

    public function receiveTransfer(InventoryTransfer $transfer, ?int $receivedBy = null): InventoryTransfer
    {
        return $this->transfers->receive($transfer, $receivedBy);
    }

    public function cancelTransfer(InventoryTransfer $transfer, ?int $cancelledBy = null): InventoryTransfer
    {
        return $this->transfers->cancel($transfer, $cancelledBy);
    }

    public function adjust(StockAdjustmentData $data): InventoryAdjustment
    {
        return $this->adjustments->create($data);
    }

    public function postAdjustment(InventoryAdjustment $adjustment, ?int $postedBy = null): InventoryAdjustment
    {
        return $this->adjustments->post($adjustment, $postedBy);
    }

    public function reverse(
        InventoryMovement|InventoryAdjustment|InventoryTransfer $record,
        ?int $reversedBy = null,
    ): InventoryMovement|InventoryAdjustment|InventoryTransfer {
        if ($record instanceof InventoryAdjustment) {
            return $this->adjustments->reverse($record, $reversedBy);
        }
        if ($record instanceof InventoryTransfer) {
            return $this->transfers->reverse($record, $reversedBy);
        }

        return $this->movements->reverse($record, $reversedBy);
    }
}
