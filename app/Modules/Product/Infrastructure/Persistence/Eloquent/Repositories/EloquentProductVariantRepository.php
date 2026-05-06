<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Product\Domain\Entities\ProductVariant;
use Modules\Product\Domain\RepositoryInterfaces\ProductVariantRepositoryInterface;
use Modules\Product\Infrastructure\Persistence\Eloquent\Models\ProductVariantModel;

class EloquentProductVariantRepository extends EloquentRepository implements ProductVariantRepositoryInterface
{
    public function __construct(ProductVariantModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(
            fn (ProductVariantModel $model): ProductVariant => $this->mapModelToDomainEntity($model)
        );
    }

    public function save(ProductVariant $productVariant): ProductVariant
    {
        $data = [
            'tenant_id' => $productVariant->getTenantId(),
            'org_unit_id' => $productVariant->getOrgUnitId(),
            'product_id' => $productVariant->getProductId(),
            'sku' => $productVariant->getSku(),
            'name' => $productVariant->getName(),
            'is_default' => $productVariant->isDefault(),
            'is_active' => $productVariant->isActive(),
            'purchase_price' => $productVariant->getPurchasePrice(),
            'sales_price' => $productVariant->getSalesPrice(),
            'metadata' => $productVariant->getMetadata(),
            'row_version' => $productVariant->getRowVersion(),
        ];

        if ($productVariant->getId()) {
            $model = $this->update($productVariant->getId(), $data);
        } else {
            $model = $this->create($data);
        }

        /** @var ProductVariantModel $model */

        return $this->toDomainEntity($model);
    }

    public function findByProductAndSku(int $productId, string $sku, ?int $tenantId = null): ?ProductVariant
    {
        $query = $this->newScopedQuery()
            ->where('product_id', $productId)
            ->where('sku', $sku);

        if ($tenantId !== null) {
            $query->where('tenant_id', $tenantId);
        }

        /** @var ProductVariantModel|null $model */
        $model = $query->first();

        return $model ? $this->toDomainEntity($model) : null;
    }

    public function clearDefaultForProduct(int $tenantId, int $productId, ?int $exceptVariantId = null): void
    {
        $query = $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('product_id', $productId)
            ->where('is_default', true);

        if ($exceptVariantId !== null) {
            $query->where('id', '!=', $exceptVariantId);
        }

        $query->update(['is_default' => false]);
    }

    public function find(int|string $id, array $columns = ['*']): ?ProductVariant
    {
        return parent::find($id, $columns);
    }

    private function mapModelToDomainEntity(ProductVariantModel $model): ProductVariant
    {
        return new ProductVariant(
            id: (int) $model->id,
            productId: (int) $model->product_id,
            tenantId: $model->tenant_id !== null ? (int) $model->tenant_id : null,
            orgUnitId: $model->org_unit_id !== null ? (int) $model->org_unit_id : null,
            sku: $model->sku,
            name: (string) $model->name,
            isDefault: (bool) $model->is_default,
            isActive: (bool) $model->is_active,
            purchasePrice: $model->purchase_price !== null ? (string) $model->purchase_price : null,
            salesPrice: $model->sales_price !== null ? (string) $model->sales_price : null,
            metadata: is_array($model->metadata) ? $model->metadata : null,
            rowVersion: (int) ($model->row_version ?? 1),
            createdAt: $model->created_at,
            updatedAt: $model->updated_at,
        );
    }
}
