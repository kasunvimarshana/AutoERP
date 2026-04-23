<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Product\Domain\Entities\Product;
use Modules\Product\Domain\RepositoryInterfaces\ProductRepositoryInterface;
use Modules\Product\Infrastructure\Persistence\Eloquent\Models\ProductModel;

class EloquentProductRepository extends EloquentRepository implements ProductRepositoryInterface
{
    public function __construct(ProductModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (ProductModel $model): Product => $this->mapModelToDomainEntity($model));
    }

    public function save(Product $product): Product
    {
        $data = [
            'tenant_id' => $product->getTenantId(),
            'category_id' => $product->getCategoryId(),
            'brand_id' => $product->getBrandId(),
            'org_unit_id' => $product->getOrgUnitId(),
            'type' => $product->getType(),
            'name' => $product->getName(),
            'image_path' => $product->getImagePath(),
            'slug' => $product->getSlug(),
            'sku' => $product->getSku(),
            'description' => $product->getDescription(),
            'base_uom_id' => $product->getBaseUomId(),
            'purchase_uom_id' => $product->getPurchaseUomId(),
            'sales_uom_id' => $product->getSalesUomId(),
            'tax_group_id' => $product->getTaxGroupId(),
            'uom_conversion_factor' => $product->getUomConversionFactor(),
            'is_batch_tracked' => $product->isBatchTracked(),
            'is_lot_tracked' => $product->isLotTracked(),
            'is_serial_tracked' => $product->isSerialTracked(),
            'valuation_method' => $product->getValuationMethod(),
            'standard_cost' => $product->getStandardCost(),
            'income_account_id' => $product->getIncomeAccountId(),
            'cogs_account_id' => $product->getCogsAccountId(),
            'inventory_account_id' => $product->getInventoryAccountId(),
            'expense_account_id' => $product->getExpenseAccountId(),
            'is_active' => $product->isActive(),
            'status' => $product->getStatus(),
            'metadata' => $product->getMetadata(),
        ];

        if ($product->getId()) {
            $model = $this->update($product->getId(), $data);
        } else {
            $model = $this->create($data);
        }

        /** @var ProductModel $model */

        return $this->toDomainEntity($model);
    }

    public function findByTenantAndSku(int $tenantId, string $sku): ?Product
    {
        /** @var ProductModel|null $model */
        $model = $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('sku', $sku)
            ->first();

        return $model ? $this->toDomainEntity($model) : null;
    }

    public function find(int|string $id, array $columns = ['*']): ?Product
    {
        return parent::find($id, $columns);
    }

    private function mapModelToDomainEntity(ProductModel $model): Product
    {
        return new Product(
            tenantId: (int) $model->tenant_id,
            categoryId: $model->category_id !== null ? (int) $model->category_id : null,
            brandId: $model->brand_id !== null ? (int) $model->brand_id : null,
            orgUnitId: $model->org_unit_id !== null ? (int) $model->org_unit_id : null,
            type: (string) $model->type,
            name: (string) $model->name,
            imagePath: $model->image_path,
            slug: (string) $model->slug,
            sku: $model->sku,
            description: $model->description,
            baseUomId: (int) $model->base_uom_id,
            purchaseUomId: $model->purchase_uom_id !== null ? (int) $model->purchase_uom_id : null,
            salesUomId: $model->sales_uom_id !== null ? (int) $model->sales_uom_id : null,
            taxGroupId: $model->tax_group_id !== null ? (int) $model->tax_group_id : null,
            uomConversionFactor: (string) $model->uom_conversion_factor,
            isBatchTracked: (bool) $model->is_batch_tracked,
            isLotTracked: (bool) $model->is_lot_tracked,
            isSerialTracked: (bool) $model->is_serial_tracked,
            valuationMethod: (string) $model->valuation_method,
            standardCost: $model->standard_cost !== null ? (string) $model->standard_cost : null,
            incomeAccountId: $model->income_account_id !== null ? (int) $model->income_account_id : null,
            cogsAccountId: $model->cogs_account_id !== null ? (int) $model->cogs_account_id : null,
            inventoryAccountId: $model->inventory_account_id !== null ? (int) $model->inventory_account_id : null,
            expenseAccountId: $model->expense_account_id !== null ? (int) $model->expense_account_id : null,
            isActive: (bool) $model->is_active,
            status: (string) ($model->status ?? 'draft'),
            metadata: is_array($model->metadata) ? $model->metadata : null,
            id: (int) $model->id,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at,
        );
    }
}
