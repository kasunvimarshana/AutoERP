<?php

declare(strict_types=1);

namespace Modules\Product\Application\Handlers;

use App\Shared\Abstractions\BaseHandler;
use Modules\Product\Application\Commands\DeleteProductCommand;
use Modules\Product\Domain\Contracts\ProductRepositoryInterface;

class DeleteProductHandler extends BaseHandler
{
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
    ) {}

    public function handle(DeleteProductCommand $command): void
    {
        $this->transaction(function () use ($command): void {
            $product = $this->productRepository->findById($command->id, $command->tenantId);

            if ($product === null) {
                throw new \DomainException("Product with ID {$command->id} not found.");
            }

            $this->productRepository->delete($command->id, $command->tenantId);
        });
    }
}
