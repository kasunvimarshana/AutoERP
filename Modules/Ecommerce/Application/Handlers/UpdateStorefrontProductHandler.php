<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Application\Handlers;

use App\Shared\Abstractions\BaseHandler;
use Illuminate\Pipeline\Pipeline;
use Modules\Core\Application\Pipes\AuditLogPipe;
use Modules\Core\Application\Pipes\ValidateCommandPipe;
use Modules\Ecommerce\Application\Commands\UpdateStorefrontProductCommand;
use Modules\Ecommerce\Domain\Contracts\StorefrontProductRepositoryInterface;
use Modules\Ecommerce\Domain\Entities\StorefrontProduct;

class UpdateStorefrontProductHandler extends BaseHandler
{
    public function __construct(
        private readonly StorefrontProductRepositoryInterface $repository,
        private readonly Pipeline $pipeline,
    ) {}

    public function handle(UpdateStorefrontProductCommand $command): StorefrontProduct
    {
        return $this->transaction(function () use ($command): StorefrontProduct {
            return $this->pipeline
                ->send($command)
                ->through([
                    ValidateCommandPipe::class,
                    AuditLogPipe::class,
                ])
                ->then(function (UpdateStorefrontProductCommand $cmd): StorefrontProduct {
                    $product = $this->repository->findById($cmd->id, $cmd->tenantId);
                    if ($product === null) {
                        throw new \DomainException("Storefront product with ID {$cmd->id} not found.");
                    }

                    return $this->repository->save(new StorefrontProduct(
                        id: $cmd->id,
                        tenantId: $cmd->tenantId,
                        productId: $product->productId,
                        slug: $cmd->slug,
                        name: $cmd->name,
                        description: $cmd->description,
                        price: $cmd->price,
                        currency: $cmd->currency,
                        isActive: $cmd->isActive,
                        isFeatured: $cmd->isFeatured,
                        sortOrder: $cmd->sortOrder,
                        createdAt: $product->createdAt,
                        updatedAt: null,
                    ));
                });
        });
    }
}
