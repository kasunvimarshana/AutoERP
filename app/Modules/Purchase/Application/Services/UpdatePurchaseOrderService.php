<?php

declare(strict_types=1);

namespace Modules\Purchase\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Purchase\Application\Contracts\UpdatePurchaseOrderServiceInterface;
use Modules\Purchase\Application\DTOs\PurchaseOrderData;
use Modules\Purchase\Domain\Entities\PurchaseOrder;
use Modules\Purchase\Domain\Exceptions\PurchaseOrderNotFoundException;
use Modules\Purchase\Domain\RepositoryInterfaces\PurchaseOrderRepositoryInterface;

class UpdatePurchaseOrderService extends BaseService implements UpdatePurchaseOrderServiceInterface
{
    public function __construct(private readonly PurchaseOrderRepositoryInterface $repo)
    {
        parent::__construct($repo);
    }

    protected function handle(array $data): PurchaseOrder
    {
        $id = (int) ($data['id'] ?? 0);
        $entity = $this->repo->find($id);

        if (! $entity) {
            throw new PurchaseOrderNotFoundException($id);
        }

        $dto = PurchaseOrderData::fromArray($data);

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

        return $this->repo->save($entity);
    }
}
