<?php

declare(strict_types=1);

namespace Modules\Product\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Product\Application\Contracts\UpdateProductServiceInterface;
use Modules\Product\Application\DTOs\ProductData;
use Modules\Product\Domain\Entities\Product;
use Modules\Product\Domain\Exceptions\ProductNotFoundException;
use Modules\Product\Domain\RepositoryInterfaces\ProductRepositoryInterface;

class UpdateProductService extends BaseService implements UpdateProductServiceInterface
{
    public function __construct(private readonly ProductRepositoryInterface $productRepository)
    {
        parent::__construct($productRepository);
    }

    protected function handle(array $data): Product
    {
        $id = (int) ($data['id'] ?? 0);
        $product = $this->productRepository->find($id);

        if (! $product) {
            throw new ProductNotFoundException($id);
        }

        $dto = ProductData::fromArray($data);

        $product->update(
            type: $dto->type,
            name: $dto->name,
            slug: $dto->slug,
            baseUomId: $dto->base_uom_id,
            imagePath: $dto->image_path,
            taxGroupId: $dto->tax_group_id,
            categoryId: $dto->category_id,
            brandId: $dto->brand_id,
            orgUnitId: $dto->org_unit_id,
            sku: $dto->sku,
            description: $dto->description,
            purchaseUomId: $dto->purchase_uom_id,
            salesUomId: $dto->sales_uom_id,
            uomConversionFactor: $dto->uom_conversion_factor,
            isBatchTracked: $dto->is_batch_tracked,
            isLotTracked: $dto->is_lot_tracked,
            isSerialTracked: $dto->is_serial_tracked,
            valuationMethod: $dto->valuation_method,
            standardCost: $dto->standard_cost,
            incomeAccountId: $dto->income_account_id,
            cogsAccountId: $dto->cogs_account_id,
            inventoryAccountId: $dto->inventory_account_id,
            expenseAccountId: $dto->expense_account_id,
            isActive: $dto->is_active,
            metadata: $dto->metadata,
        );

        return $this->productRepository->save($product);
    }
}
