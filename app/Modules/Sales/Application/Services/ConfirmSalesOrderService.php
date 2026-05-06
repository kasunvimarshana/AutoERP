<?php

declare(strict_types=1);

namespace Modules\Sales\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Sales\Application\Contracts\ConfirmSalesOrderServiceInterface;
use Modules\Sales\Domain\Entities\SalesOrder;
use Modules\Sales\Domain\Entities\SalesOrderLine;
use Modules\Sales\Domain\Events\SalesOrderConfirmed;
use Modules\Sales\Domain\Exceptions\SalesOrderNotFoundException;
use Modules\Sales\Domain\RepositoryInterfaces\SalesOrderRepositoryInterface;

class ConfirmSalesOrderService extends BaseService implements ConfirmSalesOrderServiceInterface
{
    public function __construct(private readonly SalesOrderRepositoryInterface $salesOrderRepository)
    {
        parent::__construct($salesOrderRepository);
    }

    protected function handle(array $data): SalesOrder
    {
        $id = (int) ($data['id'] ?? 0);
        $order = $this->salesOrderRepository->find($id);

        if (! $order) {
            throw new SalesOrderNotFoundException($id);
        }

        $order->confirm();

        $saved = $this->salesOrderRepository->save($order);

        $this->addEvent(new SalesOrderConfirmed(
            tenantId: $saved->getTenantId(),
            salesOrderId: (int) $saved->getId(),
            customerId: $saved->getCustomerId(),
            warehouseId: $saved->getWarehouseId(),
            lines: array_map(
                static fn (SalesOrderLine $line): array => [
                    'product_id' => $line->getProductId(),
                    'variant_id' => $line->getVariantId(),
                    'quantity'   => $line->getOrderedQty(),
                    'uom_id'     => $line->getUomId(),
                ],
                $saved->getLines(),
            ),
        ));

        return $saved;
    }
}
