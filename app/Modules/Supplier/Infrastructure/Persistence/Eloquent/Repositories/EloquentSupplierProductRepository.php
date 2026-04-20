<?php

declare(strict_types=1);

namespace Modules\Supplier\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Supplier\Domain\Entities\SupplierProduct;
use Modules\Supplier\Domain\RepositoryInterfaces\SupplierProductRepositoryInterface;
use Modules\Supplier\Infrastructure\Persistence\Eloquent\Models\SupplierProductModel;

class EloquentSupplierProductRepository extends EloquentRepository implements SupplierProductRepositoryInterface
{
    public function __construct(SupplierProductModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (SupplierProductModel $model): SupplierProduct => $this->mapModelToDomainEntity($model));
    }

    public function save(SupplierProduct $supplierProduct): SupplierProduct
    {
        $data = [
            'tenant_id' => $supplierProduct->getTenantId(),
            'supplier_id' => $supplierProduct->getSupplierId(),
            'product_id' => $supplierProduct->getProductId(),
            'variant_id' => $supplierProduct->getVariantId(),
            'supplier_sku' => $supplierProduct->getSupplierSku(),
            'lead_time_days' => $supplierProduct->getLeadTimeDays(),
            'min_order_qty' => $supplierProduct->getMinOrderQty(),
            'is_preferred' => $supplierProduct->isPreferred(),
            'last_purchase_price' => $supplierProduct->getLastPurchasePrice(),
        ];

        if ($supplierProduct->getId()) {
            $model = $this->update($supplierProduct->getId(), $data);
        } else {
            $model = $this->create($data);
        }

        /** @var SupplierProductModel $model */

        return $this->toDomainEntity($model);
    }

    public function clearPreferredByProductVariant(int $tenantId, int $productId, ?int $variantId, ?int $excludeId = null): void
    {
        $query = $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('product_id', $productId)
            ->where('is_preferred', true);

        if ($variantId === null) {
            $query->whereNull('variant_id');
        } else {
            $query->where('variant_id', $variantId);
        }

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        $query->update(['is_preferred' => false]);
    }

    public function find(int|string $id, array $columns = ['*']): ?SupplierProduct
    {
        return parent::find($id, $columns);
    }

    private function mapModelToDomainEntity(SupplierProductModel $model): SupplierProduct
    {
        return new SupplierProduct(
            id: (int) $model->id,
            tenantId: (int) $model->tenant_id,
            supplierId: (int) $model->supplier_id,
            productId: (int) $model->product_id,
            variantId: $model->variant_id !== null ? (int) $model->variant_id : null,
            supplierSku: $model->supplier_sku,
            leadTimeDays: $model->lead_time_days !== null ? (int) $model->lead_time_days : null,
            minOrderQty: number_format((float) $model->min_order_qty, 6, '.', ''),
            isPreferred: (bool) $model->is_preferred,
            lastPurchasePrice: $model->last_purchase_price !== null ? number_format((float) $model->last_purchase_price, 6, '.', '') : null,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at,
        );
    }
}
