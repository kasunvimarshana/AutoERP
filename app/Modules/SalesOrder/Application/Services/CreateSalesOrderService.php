<?php

namespace Modules\SalesOrder\Application\Services;

use Illuminate\Support\Facades\Event;
use Modules\SalesOrder\Application\Contracts\CreateSalesOrderServiceInterface;
use Modules\SalesOrder\Application\DTOs\SalesOrderData;
use Modules\SalesOrder\Domain\Entities\SalesOrder;
use Modules\SalesOrder\Domain\Events\SalesOrderCreated;
use Modules\SalesOrder\Domain\RepositoryInterfaces\SalesOrderLineRepositoryInterface;
use Modules\SalesOrder\Domain\RepositoryInterfaces\SalesOrderRepositoryInterface;
use Modules\SalesOrder\Domain\ValueObjects\SalesOrderStatus;

class CreateSalesOrderService implements CreateSalesOrderServiceInterface
{
    public function __construct(
        private readonly SalesOrderRepositoryInterface $soRepository,
        private readonly SalesOrderLineRepositoryInterface $lineRepository,
    ) {}

    public function execute(SalesOrderData $data): SalesOrder
    {
        $totalAmount = 0.0;
        foreach ($data->lines as $line) {
            $totalAmount += (float) ($line['line_total'] ?? ($line['ordered_qty'] * $line['unit_price']));
        }

        $so = $this->soRepository->create([
            'tenant_id'              => $data->tenantId,
            'warehouse_id'           => $data->warehouseId,
            'customer_id'            => $data->customerId,
            'so_number'              => $data->soNumber,
            'status'                 => SalesOrderStatus::DRAFT,
            'total_amount'           => $totalAmount,
            'currency'               => $data->currency ?? 'USD',
            'notes'                  => $data->notes,
            'shipping_address'       => $data->shippingAddress,
            'expected_delivery_date' => $data->expectedDeliveryDate,
        ]);

        foreach ($data->lines as $line) {
            $this->lineRepository->create([
                'sales_order_id'  => $so->id,
                'product_id'      => $line['product_id'],
                'variant_id'      => $line['variant_id'] ?? null,
                'ordered_qty'     => $line['ordered_qty'],
                'unit_price'      => $line['unit_price'],
                'line_total'      => $line['line_total'] ?? ($line['ordered_qty'] * $line['unit_price']),
                'discount_amount' => $line['discount_amount'] ?? null,
                'notes'           => $line['notes'] ?? null,
            ]);
        }

        Event::dispatch(new SalesOrderCreated($so->tenantId, $so->id));

        return $so;
    }
}
