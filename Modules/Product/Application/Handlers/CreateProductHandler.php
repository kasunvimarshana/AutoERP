<?php

declare(strict_types=1);

namespace Modules\Product\Application\Handlers;

use App\Shared\Abstractions\BaseHandler;
use Illuminate\Pipeline\Pipeline;
use Modules\Core\Application\Pipes\AuditLogPipe;
use Modules\Core\Application\Pipes\ValidateCommandPipe;
use Modules\Product\Application\Commands\CreateProductCommand;
use Modules\Product\Domain\Contracts\ProductRepositoryInterface;
use Modules\Product\Domain\Entities\Product;
use Modules\Product\Domain\ValueObjects\SKU;

class CreateProductHandler extends BaseHandler
{
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
        private readonly Pipeline $pipeline,
    ) {}

    public function handle(CreateProductCommand $command): Product
    {
        return $this->transaction(function () use ($command): Product {
            return $this->pipeline
                ->send($command)
                ->through([
                    ValidateCommandPipe::class,
                    AuditLogPipe::class,
                ])
                ->then(function (CreateProductCommand $cmd): Product {
                    $existing = $this->productRepository->findBySku(
                        (new SKU($cmd->sku))->value,
                        $cmd->tenantId
                    );

                    if ($existing !== null) {
                        throw new \DomainException(
                            "A product with SKU '{$cmd->sku}' already exists in this tenant."
                        );
                    }

                    $product = new Product(
                        id: null,
                        tenantId: $cmd->tenantId,
                        sku: (new SKU($cmd->sku))->value,
                        name: $cmd->name,
                        description: $cmd->description,
                        type: $cmd->type,
                        uom: $cmd->uom,
                        buyingUom: $cmd->buyingUom,
                        sellingUom: $cmd->sellingUom,
                        costingMethod: $cmd->costingMethod,
                        costPrice: $cmd->costPrice,
                        salePrice: $cmd->salePrice,
                        barcode: $cmd->barcode,
                        status: $cmd->status,
                        createdAt: null,
                        updatedAt: null,
                    );

                    return $this->productRepository->save($product);
                });
        });
    }
}
