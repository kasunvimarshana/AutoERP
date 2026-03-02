<?php

declare(strict_types=1);

namespace Modules\Product\Application\Handlers;

use Modules\Product\Application\Commands\UpdateProductCommand;
use Modules\Product\Domain\Contracts\ProductRepositoryInterface;
use Modules\Product\Domain\Entities\Product as ProductEntity;
use Modules\Product\Domain\Enums\ProductType;
use Illuminate\Support\Facades\DB;

class UpdateProductHandler
{
    public function __construct(
        private readonly ProductRepositoryInterface $products,
    ) {}

    /**
     * Handle product update.
     *
     * @throws \DomainException If product not found.
     */
    public function handle(UpdateProductCommand $command): ProductEntity
    {
        $existing = $this->products->findById($command->id, $command->tenantId);

        if ($existing === null) {
            throw new \DomainException("Product #{$command->id} not found.");
        }

        return DB::transaction(function () use ($command, $existing): ProductEntity {
            $product = new ProductEntity(
                id: $existing->getId(),
                tenantId: $existing->getTenantId(),
                name: $command->name,
                sku: $existing->getSku(),
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
