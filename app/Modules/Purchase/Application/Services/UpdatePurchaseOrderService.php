<?php

declare(strict_types=1);

namespace Modules\Purchase\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Purchase\Application\Contracts\UpdatePurchaseOrderServiceInterface;
use Modules\Purchase\Application\DTOs\PurchaseOrderData;
use Modules\Purchase\Application\DTOs\PurchaseOrderLineData;
use Modules\Purchase\Application\Support\PurchasePricingCalculator;
use Modules\Purchase\Domain\Entities\PurchaseOrder;
use Modules\Purchase\Domain\Entities\PurchaseOrderLine;
use Modules\Purchase\Domain\Exceptions\PurchaseOrderNotFoundException;
use Modules\Purchase\Domain\RepositoryInterfaces\PurchaseOrderRepositoryInterface;

class UpdatePurchaseOrderService extends BaseService implements UpdatePurchaseOrderServiceInterface
{
    public function __construct(
        private readonly PurchaseOrderRepositoryInterface $repo,
        private readonly PurchasePricingCalculator $pricingCalculator,
    ) {
        parent::__construct($repo);
    }

    protected function handle(array $data): PurchaseOrder
    {
        $id = (int) ($data['id'] ?? 0);
        $entity = $this->repo->find($id);

        if (! $entity) {
            throw new PurchaseOrderNotFoundException($id);
        }

        $merged = $this->mergePayloadWithExistingOrder($entity, $data);
        $normalizedData = $this->pricingCalculator->normalizeOrderPayload($merged);
        $dto = PurchaseOrderData::fromArray($normalizedData);

        $entity->update(
            supplierId: $dto->supplierId,
            warehouseId: $dto->warehouseId,
            poNumber: $dto->poNumber,
            currencyId: $dto->currencyId,
            exchangeRate: $dto->exchangeRate,
            orderDate: new \DateTimeImmutable($dto->orderDate),
            orgUnitId: $dto->orgUnitId,
            expectedDate: $dto->expectedDate !== null ? new \DateTimeImmutable($dto->expectedDate) : null,
            subtotal: $dto->subtotal,
            taxTotal: $dto->taxTotal,
            discountTotal: $dto->discountTotal,
            grandTotal: $dto->grandTotal,
            notes: $dto->notes,
            metadata: $dto->metadata,
            approvedBy: $dto->approvedBy,
        );

        if ($dto->lines !== null) {
            $lines = array_map(
                static fn (array $lineData): PurchaseOrderLine => self::buildLine($dto->tenantId, $lineData),
                $dto->lines
            );
            $entity->setLines($lines);
        }

        return $this->repo->save($entity);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function mergePayloadWithExistingOrder(PurchaseOrder $order, array $payload): array
    {
        $base = [
            'id' => $order->getId(),
            'tenant_id' => $order->getTenantId(),
            'supplier_id' => $order->getSupplierId(),
            'warehouse_id' => $order->getWarehouseId(),
            'po_number' => $order->getPoNumber(),
            'currency_id' => $order->getCurrencyId(),
            'order_date' => $order->getOrderDate()->format('Y-m-d'),
            'created_by' => $order->getCreatedBy(),
            'exchange_rate' => $order->getExchangeRate(),
            'status' => $order->getStatus(),
            'expected_date' => $order->getExpectedDate()?->format('Y-m-d'),
            'org_unit_id' => $order->getOrgUnitId(),
            'subtotal' => $order->getSubtotal(),
            'tax_total' => $order->getTaxTotal(),
            'discount_total' => $order->getDiscountTotal(),
            'grand_total' => $order->getGrandTotal(),
            'notes' => $order->getNotes(),
            'metadata' => $order->getMetadata(),
            'approved_by' => $order->getApprovedBy(),
        ];

        return array_merge($base, $payload);
    }

    private static function buildLine(int $tenantId, array $lineData): PurchaseOrderLine
    {
        $lineData['tenant_id'] = $lineData['tenant_id'] ?? $tenantId;
        $lineData['purchase_order_id'] = $lineData['purchase_order_id'] ?? 0;
        $lineDto = PurchaseOrderLineData::fromArray($lineData);

        return new PurchaseOrderLine(
            tenantId: $lineDto->tenantId,
            purchaseOrderId: $lineDto->purchaseOrderId,
            productId: $lineDto->productId,
            uomId: $lineDto->uomId,
            orderedQty: $lineDto->orderedQty,
            unitPrice: $lineDto->unitPrice,
            receivedQty: $lineDto->receivedQty,
            discountPct: $lineDto->discountPct,
            variantId: $lineDto->variantId,
            description: $lineDto->description,
            taxGroupId: $lineDto->taxGroupId,
            accountId: $lineDto->accountId,
        );
    }
}
