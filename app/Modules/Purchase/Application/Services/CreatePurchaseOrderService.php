<?php

declare(strict_types=1);

namespace Modules\Purchase\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Purchase\Application\Contracts\CreatePurchaseOrderServiceInterface;
use Modules\Purchase\Application\DTOs\PurchaseOrderData;
use Modules\Purchase\Domain\Entities\PurchaseOrder;
use Modules\Purchase\Domain\RepositoryInterfaces\PurchaseOrderRepositoryInterface;

class CreatePurchaseOrderService extends BaseService implements CreatePurchaseOrderServiceInterface
{
    public function __construct(private readonly PurchaseOrderRepositoryInterface $repo)
    {
        parent::__construct($repo);
    }

    protected function handle(array $data): PurchaseOrder
    {
        $dto = PurchaseOrderData::fromArray($data);

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

        return $this->repo->save($entity);
    }
}
