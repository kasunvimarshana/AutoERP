<?php

namespace Modules\Returns\Application\Services;

use Illuminate\Support\Facades\Event;
use Modules\Returns\Application\Contracts\CreateStockReturnServiceInterface;
use Modules\Returns\Application\DTOs\StockReturnData;
use Modules\Returns\Domain\Entities\StockReturn;
use Modules\Returns\Domain\Events\StockReturnCreated;
use Modules\Returns\Domain\RepositoryInterfaces\StockReturnLineRepositoryInterface;
use Modules\Returns\Domain\RepositoryInterfaces\StockReturnRepositoryInterface;
use Modules\Returns\Domain\ValueObjects\ReturnStatus;

class CreateStockReturnService implements CreateStockReturnServiceInterface
{
    public function __construct(
        private readonly StockReturnRepositoryInterface $repository,
        private readonly StockReturnLineRepositoryInterface $lineRepository,
    ) {}

    public function execute(StockReturnData $data): StockReturn
    {
        $totalAmount = 0.0;
        foreach ($data->lines as $line) {
            $totalAmount += (float) ($line['line_total'] ?? (($line['return_qty'] ?? 0) * ($line['unit_price'] ?? 0)));
        }

        $return = $this->repository->create([
            'tenant_id'           => $data->tenantId,
            'warehouse_id'        => $data->warehouseId,
            'return_number'       => $data->returnNumber,
            'return_type'         => $data->returnType,
            'status'              => ReturnStatus::DRAFT,
            'original_order_id'   => $data->originalOrderId,
            'original_order_type' => $data->originalOrderType,
            'customer_id'         => $data->customerId,
            'supplier_id'         => $data->supplierId,
            'reason'              => $data->reason,
            'total_amount'        => $totalAmount ?: null,
            'notes'               => $data->notes,
        ]);

        foreach ($data->lines as $line) {
            $this->lineRepository->create([
                'stock_return_id'       => $return->id,
                'product_id'            => $line['product_id'],
                'variant_id'            => $line['variant_id'] ?? null,
                'return_qty'            => $line['return_qty'],
                'condition'             => $line['condition'] ?? 'good',
                'quality_check_result'  => 'pending',
                'location_id'           => $line['location_id'],
                'original_batch_id'     => $line['original_batch_id'] ?? null,
                'original_lot_number'   => $line['original_lot_number'] ?? null,
                'original_serial_number' => $line['original_serial_number'] ?? null,
                'unit_price'            => $line['unit_price'] ?? null,
                'line_total'            => $line['line_total'] ?? null,
                'restock_action'        => $line['restock_action'] ?? null,
                'notes'                 => $line['notes'] ?? null,
            ]);
        }

        Event::dispatch(new StockReturnCreated($return->tenantId, $return->id));

        return $return;
    }
}
