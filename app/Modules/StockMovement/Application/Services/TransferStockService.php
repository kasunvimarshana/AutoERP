<?php
namespace Modules\StockMovement\Application\Services;

use Illuminate\Support\Facades\Event;
use Modules\StockMovement\Application\Contracts\TransferStockServiceInterface;
use Modules\StockMovement\Application\DTOs\TransferStockData;
use Modules\StockMovement\Domain\Events\StockTransferred;
use Modules\StockMovement\Domain\RepositoryInterfaces\StockMovementRepositoryInterface;
use Modules\StockMovement\Domain\ValueObjects\MovementType;

class TransferStockService implements TransferStockServiceInterface
{
    public function __construct(private readonly StockMovementRepositoryInterface $repository) {}

    public function execute(TransferStockData $data): array
    {
        $issueRef   = $data->reference . '-OUT';
        $receiptRef = $data->reference . '-IN';

        $issue = $this->repository->create([
            'tenant_id'        => $data->tenantId,
            'product_id'       => $data->productId,
            'warehouse_id'     => $data->fromWarehouseId,
            'location_id'      => $data->fromLocationId,
            'movement_type'    => MovementType::TRANSFER_OUT,
            'quantity'         => $data->quantity,
            'reference_number' => $issueRef,
            'variant_id'       => $data->variantId,
            'batch_id'         => $data->batchId,
            'moved_at'         => now(),
        ]);

        $receipt = $this->repository->create([
            'tenant_id'           => $data->tenantId,
            'product_id'          => $data->productId,
            'warehouse_id'        => $data->toWarehouseId,
            'location_id'         => $data->toLocationId,
            'movement_type'       => MovementType::TRANSFER_IN,
            'quantity'            => $data->quantity,
            'reference_number'    => $receiptRef,
            'variant_id'          => $data->variantId,
            'batch_id'            => $data->batchId,
            'related_movement_id' => $issue->id,
            'moved_at'            => now(),
        ]);

        // Update the issue movement to reference the receipt
        $this->repository->update($issue, ['related_movement_id' => $receipt->id]);

        Event::dispatch(new StockTransferred($data->tenantId, $issue->id, $receipt->id));

        return [$issue, $receipt];
    }
}
