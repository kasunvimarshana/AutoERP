<?php

declare(strict_types=1);

namespace Modules\Product\Application\Handlers;

use App\Shared\Abstractions\BaseHandler;
use Modules\Product\Application\Commands\DeleteProductImageCommand;
use Modules\Product\Domain\Contracts\ProductImageRepositoryInterface;
use Modules\Product\Domain\Contracts\ProductRepositoryInterface;

class DeleteProductImageHandler extends BaseHandler
{
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
        private readonly ProductImageRepositoryInterface $productImageRepository,
    ) {}

    public function handle(DeleteProductImageCommand $command): void
    {
        $this->transaction(function () use ($command): void {
            $product = $this->productRepository->findById($command->productId, $command->tenantId);

            if ($product === null) {
                throw new \DomainException('Product not found.');
            }

            $this->productImageRepository->delete($command->imageId, $command->tenantId);
        });
    }
}
