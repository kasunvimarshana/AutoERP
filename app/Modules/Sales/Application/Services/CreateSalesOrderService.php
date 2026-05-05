<?php

declare(strict_types=1);

namespace Modules\Sales\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Sales\Application\Contracts\CreateSalesOrderServiceInterface;
use Modules\Sales\Application\DTOs\SalesOrderData;
use Modules\Sales\Application\DTOs\SalesOrderLineData;
use Modules\Sales\Application\Support\SalesPricingCalculator;
use Modules\Sales\Domain\Entities\SalesOrder;
use Modules\Sales\Domain\Entities\SalesOrderLine;
use Modules\Sales\Domain\RepositoryInterfaces\SalesOrderRepositoryInterface;

class CreateSalesOrderService extends BaseService implements CreateSalesOrderServiceInterface
{
    public function __construct(
        private readonly SalesOrderRepositoryInterface $salesOrderRepository,
        private readonly SalesPricingCalculator $pricingCalculator,
    ) {
        parent::__construct($salesOrderRepository);
    }

    protected function handle(array $data): SalesOrder
    {
        $normalizedData = $this->pricingCalculator->normalizeOrderPayload($data);
        $dto = SalesOrderData::fromArray($normalizedData);

        $orderDate = $dto->orderDate !== null
            ? new \DateTimeImmutable($dto->orderDate)
            : new \DateTimeImmutable;

        $requestedDeliveryDate = $dto->requestedDeliveryDate !== null
            ? new \DateTimeImmutable($dto->requestedDeliveryDate)
            : null;

        $order = new SalesOrder(
            tenantId: $dto->tenantId,
            customerId: $dto->customerId,
            warehouseId: $dto->warehouseId,
            currencyId: $dto->currencyId,
            orderDate: $orderDate,
            orgUnitId: $dto->orgUnitId,
            soNumber: $dto->soNumber,
            status: $dto->status,
            exchangeRate: $dto->exchangeRate,
            requestedDeliveryDate: $requestedDeliveryDate,
            priceListId: $dto->priceListId,
            subtotal: $dto->subtotal,
            taxTotal: $dto->taxTotal,
            discountTotal: $dto->discountTotal,
            grandTotal: $dto->grandTotal,
            notes: $dto->notes,
            metadata: $dto->metadata,
            createdBy: $dto->createdBy,
            approvedBy: $dto->approvedBy,
        );

        if ($dto->lines !== null) {
            $lines = array_map(
                static fn (array $lineData): SalesOrderLine => self::buildLine($dto->tenantId, $lineData),
                $dto->lines
            );
            $order->setLines($lines);
        }

        return $this->salesOrderRepository->save($order);
    }

    private static function buildLine(int $tenantId, array $lineData): SalesOrderLine
    {
        $lineData['tenant_id'] = $lineData['tenant_id'] ?? $tenantId;
        $lineDto = SalesOrderLineData::fromArray($lineData);

        return new SalesOrderLine(
            tenantId: $lineDto->tenantId,
            productId: $lineDto->productId,
            uomId: $lineDto->uomId,
            salesOrderId: $lineDto->salesOrderId,
            variantId: $lineDto->variantId,
            description: $lineDto->description,
            orderedQty: $lineDto->orderedQty,
            shippedQty: $lineDto->shippedQty,
            reservedQty: $lineDto->reservedQty,
            unitPrice: $lineDto->unitPrice,
            discountPct: $lineDto->discountPct,
            taxGroupId: $lineDto->taxGroupId,
            lineTotal: $lineDto->lineTotal,
            incomeAccountId: $lineDto->incomeAccountId,
            batchId: $lineDto->batchId,
            serialId: $lineDto->serialId,
        );
    }
}
