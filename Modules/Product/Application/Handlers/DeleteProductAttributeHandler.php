<?php

declare(strict_types=1);

namespace Modules\Product\Application\Handlers;

use App\Shared\Abstractions\BaseHandler;
use Modules\Product\Application\Commands\DeleteProductAttributeCommand;
use Modules\Product\Domain\Contracts\ProductAttributeRepositoryInterface;
use Modules\Product\Domain\Contracts\ProductRepositoryInterface;

class DeleteProductAttributeHandler extends BaseHandler
{
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
        private readonly ProductAttributeRepositoryInterface $productAttributeRepository,
    ) {}

    public function handle(DeleteProductAttributeCommand $command): void
    {
        $this->transaction(function () use ($command): void {
            $product = $this->productRepository->findById($command->productId, $command->tenantId);

            if ($product === null) {
                throw new \DomainException('Product not found.');
            }

            $this->productAttributeRepository->delete($command->attributeId, $command->tenantId);
        });
    }
}
