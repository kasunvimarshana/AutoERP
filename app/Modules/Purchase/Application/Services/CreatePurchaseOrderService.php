<?php

declare(strict_types=1);

namespace Modules\Purchase\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Purchase\Application\Contracts\CreatePurchaseOrderServiceInterface;
use Modules\Purchase\Application\DTOs\PurchaseOrderData;
use Modules\Purchase\Application\DTOs\PurchaseOrderLineData;
use Modules\Purchase\Application\Support\PurchasePricingCalculator;
use Modules\Purchase\Domain\Entities\PurchaseOrder;
use Modules\Purchase\Domain\Entities\PurchaseOrderLine;
use Modules\Purchase\Domain\RepositoryInterfaces\PurchaseOrderRepositoryInterface;

class CreatePurchaseOrderService extends BaseService implements CreatePurchaseOrderServiceInterface
{
    public function __construct(
        private readonly PurchaseOrderRepositoryInterface $repo,
        private readonly PurchasePricingCalculator $pricingCalculator,
    ) {
        parent::__construct($repo);
    }

    protected function handle(array $data): PurchaseOrder
    {
        $normalizedData = $this->pricingCalculator->normalizeOrderPayload($data);
        $dto = PurchaseOrderData::fromArray($normalizedData);

        $entity = new PurchaseOrder(
            tenantId: $dto->tenantId,
            supplierId: $dto->supplierId,
            warehouseId: $dto->warehouseId,
            poNumber: $dto->poNumber,
            status: $dto->status,
            currencyId: $dto->currencyId,
            exchangeRate: $dto->exchangeRate,
            orderDate: new \DateTimeImmutable($dto->orderDate),
            createdBy: $dto->createdBy,
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
