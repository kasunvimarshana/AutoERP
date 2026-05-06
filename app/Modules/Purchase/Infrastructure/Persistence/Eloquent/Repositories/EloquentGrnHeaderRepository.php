<?php

declare(strict_types=1);

namespace Modules\Purchase\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Purchase\Domain\Entities\GrnHeader;
use Modules\Purchase\Domain\RepositoryInterfaces\GrnHeaderRepositoryInterface;
use Modules\Purchase\Infrastructure\Persistence\Eloquent\Models\GrnHeaderModel;

class EloquentGrnHeaderRepository extends EloquentRepository implements GrnHeaderRepositoryInterface
{
    public function __construct(GrnHeaderModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (GrnHeaderModel $m): GrnHeader => $this->mapToDomain($m));
    }

    public function save(GrnHeader $entity): GrnHeader
    {
        $data = [
            'tenant_id' => $entity->getTenantId(),
            'supplier_id' => $entity->getSupplierId(),
            'warehouse_id' => $entity->getWarehouseId(),
            'purchase_order_id' => $entity->getPurchaseOrderId(),
            'grn_number' => $entity->getGrnNumber(),
            'status' => $entity->getStatus(),
            'received_date' => $entity->getReceivedDate()->format('Y-m-d'),
            'currency_id' => $entity->getCurrencyId(),
            'exchange_rate' => $entity->getExchangeRate(),
            'notes' => $entity->getNotes(),
            'metadata' => $entity->getMetadata(),
            'created_by' => $entity->getCreatedBy(),
        ];

        if ($entity->getId()) {
            $model = $this->update($entity->getId(), $data);
        } else {
            $model = $this->create($data);
        }

        return $this->toDomainEntity($model);
    }

    public function find(int|string $id, array $columns = ['*']): ?GrnHeader
    {
        return parent::find($id, $columns);
    }

    private function mapToDomain(GrnHeaderModel $m): GrnHeader
    {
        return new GrnHeader(
            tenantId: (int) $m->tenant_id,
            supplierId: (int) $m->supplier_id,
            warehouseId: (int) $m->warehouse_id,
            grnNumber: (string) $m->grn_number,
            status: (string) $m->status,
            receivedDate: $m->received_date instanceof \DateTimeInterface ? $m->received_date : new \DateTimeImmutable((string) $m->received_date),
            currencyId: (int) $m->currency_id,
            exchangeRate: (string) $m->exchange_rate,
            createdBy: (int) $m->created_by,
            purchaseOrderId: $m->purchase_order_id !== null ? (int) $m->purchase_order_id : null,
            notes: $m->notes !== null ? (string) $m->notes : null,
            metadata: $m->metadata,
            id: (int) $m->id,
            createdAt: $m->created_at,
            updatedAt: $m->updated_at,
        );
    }
}
