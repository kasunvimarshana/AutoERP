<?php

declare(strict_types=1);

namespace Modules\Product\Application\Services;

use Modules\Core\Application\Contracts\SlugGeneratorInterface;
use Modules\Core\Application\Services\BaseService;
use Modules\Product\Application\Contracts\CreateProductServiceInterface;
use Modules\Product\Application\Contracts\RefreshProductSearchProjectionServiceInterface;
use Modules\Product\Application\DTOs\ProductData;
use Modules\Product\Domain\Entities\Product;
use Modules\Product\Domain\RepositoryInterfaces\ProductRepositoryInterface;

class CreateProductService extends BaseService implements CreateProductServiceInterface
{
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
        private readonly SlugGeneratorInterface $slugGenerator,
        private readonly RefreshProductSearchProjectionServiceInterface $refreshProjectionService,
    ) {
        parent::__construct($productRepository);
    }

    protected function handle(array $data): Product
    {
        $data['slug'] = $this->slugGenerator->generate(
            preferredValue: isset($data['slug']) ? (string) $data['slug'] : null,
            sourceValue: isset($data['name']) ? (string) $data['name'] : null,
            fallback: 'product'
        );

        $dto = ProductData::fromArray($data);

        $product = new Product(
            tenantId: $dto->tenant_id,
            categoryId: $dto->category_id,
            brandId: $dto->brand_id,
            orgUnitId: $dto->org_unit_id,
            type: $dto->type,
            name: $dto->name,
            slug: $dto->slug,
            sku: $dto->sku,
            description: $dto->description,
            baseUomId: $dto->base_uom_id,
            imagePath: $dto->image_path,
            taxGroupId: $dto->tax_group_id,
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

        $saved = $this->productRepository->save($product);
        $savedId = $saved->getId();

        if ($savedId !== null) {
            $this->refreshProjectionService->execute($saved->getTenantId(), $savedId);
        }

        return $saved;
    }
}
