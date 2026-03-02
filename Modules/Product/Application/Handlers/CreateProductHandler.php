<?php

declare(strict_types=1);

namespace Modules\Product\Application\Handlers;

use Modules\Product\Application\Commands\CreateProductCommand;
use Modules\Product\Domain\Contracts\ProductRepositoryInterface;
use Modules\Product\Domain\Entities\Product as ProductEntity;
use Modules\Product\Domain\Enums\ProductType;
use Modules\Product\Domain\ValueObjects\SKU;
use Illuminate\Support\Facades\DB;

class CreateProductHandler
{
    public function __construct(
        private readonly ProductRepositoryInterface $products
    ) {}

    /**
     * Handle product creation.
     *
     * @throws \DomainException If SKU already exists for this tenant.
     */
    public function handle(CreateProductCommand $command): ProductEntity
    {
        $sku = new SKU($command->sku);

        if ($this->products->skuExistsForTenant($sku, $command->tenantId)) {
            throw new \DomainException("SKU \"{$sku}\" already exists for this tenant.");
        }

        return DB::transaction(function () use ($command, $sku): ProductEntity {
            $product = new ProductEntity(
                id: 0,
                tenantId: $command->tenantId,
                name: $command->name,
                sku: $sku,
                categoryId: $command->categoryId,
                brandId: $command->brandId,
                unitId: $command->unitId,
                type: ProductType::from($command->type),
                costPrice: bcadd($command->costPrice, '0', 4),
                sellingPrice: bcadd($command->sellingPrice, '0', 4),
                reorderPoint: bcadd($command->reorderPoint, '0', 4),
                isActive: $command->isActive,
                description: $command->description,
            );

            return $this->products->save($product);
        });
    }
}
