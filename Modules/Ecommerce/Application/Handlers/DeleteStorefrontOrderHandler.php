<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Application\Handlers;

use App\Shared\Abstractions\BaseHandler;
use Modules\Ecommerce\Application\Commands\DeleteStorefrontOrderCommand;
use Modules\Ecommerce\Domain\Contracts\StorefrontOrderRepositoryInterface;

class DeleteStorefrontOrderHandler extends BaseHandler
{
    public function __construct(
        private readonly StorefrontOrderRepositoryInterface $repository,
    ) {}

    public function handle(DeleteStorefrontOrderCommand $command): void
    {
        $this->transaction(function () use ($command): void {
            $order = $this->repository->findById($command->id, $command->tenantId);
            if ($order === null) {
                throw new \DomainException("Storefront order with ID {$command->id} not found.");
            }

            $this->repository->delete($command->id, $command->tenantId);
        });
    }
}
