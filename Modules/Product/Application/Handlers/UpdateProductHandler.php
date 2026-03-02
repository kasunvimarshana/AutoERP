<?php

declare(strict_types=1);

namespace Modules\Product\Application\Handlers;

use App\Shared\Abstractions\BaseHandler;
use Illuminate\Pipeline\Pipeline;
use Modules\Core\Application\Pipes\AuditLogPipe;
use Modules\Core\Application\Pipes\ValidateCommandPipe;
use Modules\Product\Application\Commands\UpdateProductCommand;
use Modules\Product\Domain\Contracts\ProductRepositoryInterface;
use Modules\Product\Domain\Entities\Product;

class UpdateProductHandler extends BaseHandler
{
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
        private readonly Pipeline $pipeline,
    ) {}

    public function handle(UpdateProductCommand $command): Product
    {
        return $this->transaction(function () use ($command): Product {
            $product = $this->productRepository->findById($command->id, $command->tenantId);

            if ($product === null) {
                throw new \DomainException("Product with ID {$command->id} not found.");
            }

            return $this->pipeline
                ->send($command)
                ->through([
                    ValidateCommandPipe::class,
                    AuditLogPipe::class,
                ])
                ->then(function (UpdateProductCommand $cmd) use ($product): Product {
                    $updated = new Product(
                        id: $product->id,
                        tenantId: $product->tenantId,
                        sku: $product->sku,
                        name: $cmd->name,
                        description: $cmd->description,
                        type: $product->type,
                        uom: $cmd->uom,
                        buyingUom: $cmd->buyingUom,
                        sellingUom: $cmd->sellingUom,
                        costingMethod: $cmd->costingMethod,
                        costPrice: $cmd->costPrice,
                        salePrice: $cmd->salePrice,
                        barcode: $cmd->barcode,
                        status: $cmd->status,
                        createdAt: $product->createdAt,
                        updatedAt: null,
                    );

                    return $this->productRepository->save($updated);
                });
        });
    }
}
