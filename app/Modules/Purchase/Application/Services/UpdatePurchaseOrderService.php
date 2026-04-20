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
            supplierId: $dto->supplier_id,
            warehouseId: $dto->warehouse_id,
            poNumber: $dto->po_number,
            currencyId: $dto->currency_id,
            exchangeRate: $dto->exchange_rate,
            orderDate: new \DateTimeImmutable($dto->order_date),
            orgUnitId: $dto->org_unit_id,
            expectedDate: $dto->expected_date !== null ? new \DateTimeImmutable($dto->expected_date) : null,
            subtotal: $dto->subtotal,
            taxTotal: $dto->tax_total,
            discountTotal: $dto->discount_total,
            grandTotal: $dto->grand_total,
            notes: $dto->notes,
            metadata: $dto->metadata,
            approvedBy: $dto->approved_by,
        );

        return $this->repo->save($entity);
    }
}
