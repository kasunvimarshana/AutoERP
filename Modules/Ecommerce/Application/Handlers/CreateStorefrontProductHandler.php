<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Application\Handlers;

use App\Shared\Abstractions\BaseHandler;
use Illuminate\Pipeline\Pipeline;
use Modules\Core\Application\Pipes\AuditLogPipe;
use Modules\Core\Application\Pipes\ValidateCommandPipe;
use Modules\Ecommerce\Application\Commands\CreateStorefrontProductCommand;
use Modules\Ecommerce\Domain\Contracts\StorefrontProductRepositoryInterface;
use Modules\Ecommerce\Domain\Entities\StorefrontProduct;

class CreateStorefrontProductHandler extends BaseHandler
{
    public function __construct(
        private readonly StorefrontProductRepositoryInterface $repository,
        private readonly Pipeline $pipeline,
    ) {}

    public function handle(CreateStorefrontProductCommand $command): StorefrontProduct
    {
        return $this->transaction(function () use ($command): StorefrontProduct {
            return $this->pipeline
                ->send($command)
                ->through([
                    ValidateCommandPipe::class,
                    AuditLogPipe::class,
                ])
                ->then(function (CreateStorefrontProductCommand $cmd): StorefrontProduct {
                    $existing = $this->repository->findBySlug($cmd->slug, $cmd->tenantId);
                    if ($existing !== null) {
                        throw new \DomainException("A storefront product with slug '{$cmd->slug}' already exists.");
                    }

                    return $this->repository->save(new StorefrontProduct(
                        id: null,
                        tenantId: $cmd->tenantId,
                        productId: $cmd->productId,
                        slug: $cmd->slug,
                        name: $cmd->name,
                        description: $cmd->description,
                        price: $cmd->price,
                        currency: $cmd->currency,
                        isActive: $cmd->isActive,
                        isFeatured: $cmd->isFeatured,
                        sortOrder: $cmd->sortOrder,
                        createdAt: null,
                        updatedAt: null,
                    ));
                });
        });
    }
}
