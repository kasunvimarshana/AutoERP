<?php

declare(strict_types=1);

namespace Modules\Sales\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Sales\Application\Contracts\UpdateSalesOrderServiceInterface;
use Modules\Sales\Application\DTOs\SalesOrderData;
use Modules\Sales\Application\DTOs\SalesOrderLineData;
use Modules\Sales\Application\Support\SalesPricingCalculator;
use Modules\Sales\Domain\Entities\SalesOrder;
use Modules\Sales\Domain\Entities\SalesOrderLine;
use Modules\Sales\Domain\Exceptions\SalesOrderNotFoundException;
use Modules\Sales\Domain\RepositoryInterfaces\SalesOrderRepositoryInterface;

class UpdateSalesOrderService extends BaseService implements UpdateSalesOrderServiceInterface
{
    public function __construct(
        private readonly SalesOrderRepositoryInterface $salesOrderRepository,
        private readonly SalesPricingCalculator $pricingCalculator,
    ) {
        parent::__construct($salesOrderRepository);
    }

    protected function handle(array $data): SalesOrder
    {
        $id = (int) ($data['id'] ?? 0);
        $order = $this->salesOrderRepository->find($id);

        if (! $order) {
            throw new SalesOrderNotFoundException($id);
        }

        $payload = $this->mergePayloadWithExistingOrder($order, $data);
        $payload = $this->pricingCalculator->normalizeOrderPayload($payload);
        $dto = SalesOrderData::fromArray($payload);

        if ($order->getTenantId() !== $dto->tenantId) {
            throw new SalesOrderNotFoundException($id);
        }

        $orderDate = $dto->orderDate !== null
            ? new \DateTimeImmutable($dto->orderDate)
            : $order->getOrderDate();

        $requestedDeliveryDate = $dto->requestedDeliveryDate !== null
            ? new \DateTimeImmutable($dto->requestedDeliveryDate)
            : $order->getRequestedDeliveryDate();

        $order->update(
            customerId: $dto->customerId,
            warehouseId: $dto->warehouseId,
            currencyId: $dto->currencyId,
            orderDate: $orderDate,
            orgUnitId: $dto->orgUnitId,
            soNumber: $dto->soNumber,
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

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function mergePayloadWithExistingOrder(SalesOrder $order, array $payload): array
    {
        $merged = [
            'tenant_id' => $order->getTenantId(),
            'customer_id' => $order->getCustomerId(),
            'warehouse_id' => $order->getWarehouseId(),
            'currency_id' => $order->getCurrencyId(),
            'org_unit_id' => $order->getOrgUnitId(),
            'so_number' => $order->getSoNumber(),
            'status' => $order->getStatus(),
            'exchange_rate' => $order->getExchangeRate(),
            'order_date' => $order->getOrderDate()->format('Y-m-d'),
            'requested_delivery_date' => $order->getRequestedDeliveryDate()?->format('Y-m-d'),
            'price_list_id' => $order->getPriceListId(),
            'subtotal' => $order->getSubtotal(),
            'tax_total' => $order->getTaxTotal(),
            'discount_total' => $order->getDiscountTotal(),
            'grand_total' => $order->getGrandTotal(),
            'notes' => $order->getNotes(),
            'metadata' => $order->getMetadata(),
            'created_by' => $order->getCreatedBy(),
            'approved_by' => $order->getApprovedBy(),
        ];

        return array_replace($merged, $payload);
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
            id: $lineDto->id,
        );
    }
}
