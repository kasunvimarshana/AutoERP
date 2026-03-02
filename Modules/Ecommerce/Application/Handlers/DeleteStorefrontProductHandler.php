<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Application\Handlers;

use App\Shared\Abstractions\BaseHandler;
use Modules\Ecommerce\Application\Commands\DeleteStorefrontProductCommand;
use Modules\Ecommerce\Domain\Contracts\StorefrontProductRepositoryInterface;

class DeleteStorefrontProductHandler extends BaseHandler
{
    public function __construct(
        private readonly StorefrontProductRepositoryInterface $repository,
    ) {}

    public function handle(DeleteStorefrontProductCommand $command): void
    {
        $this->transaction(function () use ($command): void {
            $product = $this->repository->findById($command->id, $command->tenantId);
            if ($product === null) {
                throw new \DomainException("Storefront product with ID {$command->id} not found.");
            }

            $this->repository->delete($command->id, $command->tenantId);
        });
    }
}
