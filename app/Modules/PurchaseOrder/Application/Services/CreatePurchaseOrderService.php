<?php
namespace Modules\PurchaseOrder\Application\Services;
use Illuminate\Support\Facades\Event;
use Modules\PurchaseOrder\Application\Contracts\CreatePurchaseOrderServiceInterface;
use Modules\PurchaseOrder\Application\DTOs\PurchaseOrderData;
use Modules\PurchaseOrder\Domain\Entities\PurchaseOrder;
use Modules\PurchaseOrder\Domain\Events\PurchaseOrderCreated;
use Modules\PurchaseOrder\Domain\RepositoryInterfaces\PurchaseOrderLineRepositoryInterface;
use Modules\PurchaseOrder\Domain\RepositoryInterfaces\PurchaseOrderRepositoryInterface;
use Modules\PurchaseOrder\Domain\ValueObjects\PurchaseOrderStatus;

class CreatePurchaseOrderService implements CreatePurchaseOrderServiceInterface
{
    public function __construct(
        private readonly PurchaseOrderRepositoryInterface $poRepository,
        private readonly PurchaseOrderLineRepositoryInterface $lineRepository,
    ) {}

    public function execute(PurchaseOrderData $data): PurchaseOrder
    {
        $totalAmount = 0.0;
        foreach ($data->lines as $line) {
            $totalAmount += (float) ($line['line_total'] ?? ($line['ordered_qty'] * $line['unit_cost']));
        }

        $po = $this->poRepository->create([
            'tenant_id'              => $data->tenantId,
            'warehouse_id'           => $data->warehouseId,
            'supplier_id'            => $data->supplierId,
            'po_number'              => $data->poNumber,
            'status'                 => PurchaseOrderStatus::DRAFT,
            'total_amount'           => $totalAmount,
            'currency'               => $data->currency ?? 'USD',
            'notes'                  => $data->notes,
            'expected_delivery_date' => $data->expectedDeliveryDate,
        ]);

        foreach ($data->lines as $line) {
            $this->lineRepository->create([
                'purchase_order_id' => $po->id,
                'product_id'        => $line['product_id'],
                'variant_id'        => $line['variant_id'] ?? null,
                'ordered_qty'       => $line['ordered_qty'],
                'unit_cost'         => $line['unit_cost'],
                'line_total'        => $line['line_total'] ?? ($line['ordered_qty'] * $line['unit_cost']),
                'notes'             => $line['notes'] ?? null,
            ]);
        }

        Event::dispatch(new PurchaseOrderCreated($po->tenantId, $po->id));

        return $po;
    }
}
