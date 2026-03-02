<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Application\Services;

use Modules\Ecommerce\Application\Commands\CancelStorefrontOrderCommand;
use Modules\Ecommerce\Application\Commands\DeleteStorefrontOrderCommand;
use Modules\Ecommerce\Application\Commands\UpdateStorefrontOrderStatusCommand;
use Modules\Ecommerce\Application\Handlers\CancelStorefrontOrderHandler;
use Modules\Ecommerce\Application\Handlers\DeleteStorefrontOrderHandler;
use Modules\Ecommerce\Application\Handlers\UpdateStorefrontOrderStatusHandler;
use Modules\Ecommerce\Domain\Contracts\StorefrontOrderRepositoryInterface;
use Modules\Ecommerce\Domain\Entities\StorefrontOrder;

class StorefrontOrderService
{
    public function __construct(
        private readonly StorefrontOrderRepositoryInterface $repository,
        private readonly UpdateStorefrontOrderStatusHandler $updateStatusHandler,
        private readonly CancelStorefrontOrderHandler $cancelHandler,
        private readonly DeleteStorefrontOrderHandler $deleteHandler,
    ) {}

    public function findById(int $id, int $tenantId): ?StorefrontOrder
    {
        return $this->repository->findById($id, $tenantId);
    }

    public function findAll(int $tenantId, int $page, int $perPage): array
    {
        return $this->repository->findAll($tenantId, $page, $perPage);
    }

    public function updateStatus(UpdateStorefrontOrderStatusCommand $cmd): StorefrontOrder
    {
        return $this->updateStatusHandler->handle($cmd);
    }

    public function cancel(CancelStorefrontOrderCommand $cmd): StorefrontOrder
    {
        return $this->cancelHandler->handle($cmd);
    }

    public function delete(DeleteStorefrontOrderCommand $cmd): void
    {
        $this->deleteHandler->handle($cmd);
    }

    public function findLines(int $orderId, int $tenantId): array
    {
        return $this->repository->findLines($orderId, $tenantId);
    }
}
