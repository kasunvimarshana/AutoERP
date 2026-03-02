<?php

declare(strict_types=1);

namespace Modules\Procurement\Application\Handlers;

use App\Shared\Abstractions\BaseHandler;
use Modules\Procurement\Application\Commands\DeletePurchaseOrderCommand;
use Modules\Procurement\Domain\Contracts\PurchaseOrderRepositoryInterface;

class DeletePurchaseOrderHandler extends BaseHandler
{
    public function __construct(
        private readonly PurchaseOrderRepositoryInterface $purchaseOrderRepository,
    ) {}

    public function handle(DeletePurchaseOrderCommand $command): void
    {
        $this->transaction(function () use ($command): void {
            $order = $this->purchaseOrderRepository->findById($command->id, $command->tenantId);

            if ($order === null) {
                throw new \DomainException('Purchase order not found.');
            }

            $this->purchaseOrderRepository->delete($command->id, $command->tenantId);
        });
    }
}
