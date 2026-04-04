<?php
namespace Modules\GoodsReceipt\Application\Services;

use Illuminate\Support\Facades\Event;
use Modules\GoodsReceipt\Application\Contracts\CreateGoodsReceiptServiceInterface;
use Modules\GoodsReceipt\Application\DTOs\GoodsReceiptData;
use Modules\GoodsReceipt\Domain\Entities\GoodsReceipt;
use Modules\GoodsReceipt\Domain\Events\GoodsReceiptCreated;
use Modules\GoodsReceipt\Domain\RepositoryInterfaces\GoodsReceiptLineRepositoryInterface;
use Modules\GoodsReceipt\Domain\RepositoryInterfaces\GoodsReceiptRepositoryInterface;
use Modules\GoodsReceipt\Domain\ValueObjects\GoodsReceiptStatus;

class CreateGoodsReceiptService implements CreateGoodsReceiptServiceInterface
{
    public function __construct(
        private readonly GoodsReceiptRepositoryInterface $grRepository,
        private readonly GoodsReceiptLineRepositoryInterface $lineRepository,
    ) {}

    public function execute(GoodsReceiptData $data): GoodsReceipt
    {
        $gr = $this->grRepository->create([
            'tenant_id'          => $data->tenantId,
            'warehouse_id'       => $data->warehouseId,
            'gr_number'          => $data->grNumber,
            'status'             => GoodsReceiptStatus::PENDING,
            'purchase_order_id'  => $data->purchaseOrderId,
            'supplier_id'        => $data->supplierId,
            'supplier_reference' => $data->supplierReference,
            'notes'              => $data->notes,
            'received_by'        => $data->receivedBy,
            'received_at'        => $data->receivedBy ? now() : null,
        ]);

        foreach ($data->lines as $line) {
            $this->lineRepository->create([
                'goods_receipt_id'       => $gr->id,
                'product_id'             => $line['product_id'],
                'variant_id'             => $line['variant_id'] ?? null,
                'purchase_order_line_id' => $line['purchase_order_line_id'] ?? null,
                'expected_qty'           => $line['expected_qty'],
                'received_qty'           => $line['received_qty'] ?? $line['expected_qty'],
                'location_id'            => $line['location_id'],
                'batch_id'               => $line['batch_id'] ?? null,
                'lot_number'             => $line['lot_number'] ?? null,
                'serial_number'          => $line['serial_number'] ?? null,
                'unit_cost'              => $line['unit_cost'] ?? null,
                'condition'              => $line['condition'] ?? 'good',
                'notes'                  => $line['notes'] ?? null,
            ]);
        }

        Event::dispatch(new GoodsReceiptCreated($gr->tenantId, $gr->id));

        return $gr;
    }
}
