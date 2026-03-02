<?php

declare(strict_types=1);

namespace Modules\Sales\Application\Handlers;

use App\Shared\Abstractions\BaseHandler;
use Modules\Sales\Application\Commands\DeleteSalesOrderCommand;
use Modules\Sales\Domain\Contracts\SalesOrderRepositoryInterface;

class DeleteSalesOrderHandler extends BaseHandler
{
    public function __construct(
        private readonly SalesOrderRepositoryInterface $salesOrderRepository,
    ) {}

    public function handle(DeleteSalesOrderCommand $command): void
    {
        $this->transaction(function () use ($command): void {
            $order = $this->salesOrderRepository->findById($command->id, $command->tenantId);

            if ($order === null) {
                throw new \DomainException('Sales order not found.');
            }

            $this->salesOrderRepository->delete($command->id, $command->tenantId);
        });
    }
}
