<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Product\Domain\Entities\ProductSupplierPrice;
use Modules\Product\Domain\RepositoryInterfaces\ProductSupplierPriceRepositoryInterface;
use Modules\Product\Infrastructure\Persistence\Eloquent\Models\ProductSupplierPriceModel;

class EloquentProductSupplierPriceRepository extends EloquentRepository implements ProductSupplierPriceRepositoryInterface
{
    public function __construct(ProductSupplierPriceModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (ProductSupplierPriceModel $model): ProductSupplierPrice => $this->mapModelToDomainEntity($model));
    }

    public function save(ProductSupplierPrice $entity): ProductSupplierPrice
    {
        $data = [
            'tenant_id' => $entity->getTenantId(),
            'product_id' => $entity->getProductId(),
            'variant_id' => $entity->getVariantId(),
            'supplier_id' => $entity->getSupplierId(),
            'currency_id' => $entity->getCurrencyId(),
            'uom_id' => $entity->getUomId(),
            'min_order_quantity' => $entity->getMinOrderQuantity(),
            'unit_price' => $entity->getUnitPrice(),
            'discount_percent' => $entity->getDiscountPercent(),
            'lead_time_days' => $entity->getLeadTimeDays(),
            'is_preferred' => $entity->isPreferred(),
            'is_active' => $entity->isActive(),
            'effective_from' => $entity->getEffectiveFrom()?->format('Y-m-d'),
            'effective_to' => $entity->getEffectiveTo()?->format('Y-m-d'),
            'notes' => $entity->getNotes(),
            'metadata' => $entity->getMetadata(),
        ];

        if ($entity->getId()) {
            $model = $this->update($entity->getId(), $data);
        } else {
            $model = $this->create($data);
        }

        /** @var ProductSupplierPriceModel $model */
        return $this->toDomainEntity($model);
    }

    public function find(int|string $id, array $columns = ['*']): ?ProductSupplierPrice
    {
        return parent::find($id, $columns);
    }

    private function mapModelToDomainEntity(ProductSupplierPriceModel $model): ProductSupplierPrice
    {
        return new ProductSupplierPrice(
            tenantId: (int) $model->tenant_id,
            productId: (int) $model->product_id,
            supplierId: (int) $model->supplier_id,
            uomId: (int) $model->uom_id,
            unitPrice: (string) $model->unit_price,
            variantId: $model->variant_id !== null ? (int) $model->variant_id : null,
            currencyId: $model->currency_id !== null ? (int) $model->currency_id : null,
            minOrderQuantity: (string) $model->min_order_quantity,
            discountPercent: (string) $model->discount_percent,
            leadTimeDays: (int) $model->lead_time_days,
            isPreferred: (bool) $model->is_preferred,
            isActive: (bool) $model->is_active,
            effectiveFrom: $model->effective_from ? new \DateTimeImmutable($model->effective_from->format('Y-m-d')) : null,
            effectiveTo: $model->effective_to ? new \DateTimeImmutable($model->effective_to->format('Y-m-d')) : null,
            notes: $model->notes,
            metadata: is_array($model->metadata) ? $model->metadata : null,
            id: (int) $model->id,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at,
        );
    }
}
